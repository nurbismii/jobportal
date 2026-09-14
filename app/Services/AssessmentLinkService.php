<?php

namespace App\Services;

use App\Models\AssessmentLink;
use App\Models\AssessmentLinkCandidate;
use App\Models\AssessmentResultAudit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssessmentLinkService
{
    private const HEALTH_SCHEMA = [
        'id' => 'health_status',
        'label' => 'Hasil tes kesehatan',
        'type' => 'select',
        'required' => true,
        'options' => ['Sehat', 'Tidak Sehat'],
    ];

    private const ALLOWED_TYPES = ['select', 'number', 'text'];

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, int|string> $lamaranIds
     */
    public function create(array $attributes, array $lamaranIds, int $creatorId): AssessmentLink
    {
        $type = $attributes['assessment_type'] ?? null;
        if ($type === AssessmentLink::TYPE_MCU) {
            $lamaranIds = [];
            $attributes['fields'] = [];
        }
        $fields = array_key_exists('fields', $attributes) ? $attributes['fields'] : ($attributes['form_schema'] ?? []);
        if (!is_array($fields)) {
            throw ValidationException::withMessages(['fields' => ['Field schema harus berupa array.']]);
        }
        $eligibilityFieldId = $attributes['eligibility_field_id'] ?? null;
        $pin = $attributes['pin'] ?? null;

        if (!is_string($pin) || trim($pin) === '' || strlen($pin) > 255) {
            throw ValidationException::withMessages(['pin' => ['PIN wajib diisi dan maksimal 255 karakter.']]);
        }

        $expiresAt = now('Asia/Makassar')->endOfDay();
        if ($type === AssessmentLink::TYPE_MCU && !empty($attributes['expires_on'])) {
            $validated = validator(['expires_on' => $attributes['expires_on']], [
                'expires_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            ])->validate();
            $expiresAt = Carbon::createFromFormat('!Y-m-d', $validated['expires_on'], 'Asia/Makassar')->endOfDay();
        }

        return DB::transaction(function () use ($type, $fields, $eligibilityFieldId, $pin, $creatorId, $lamaranIds, $expiresAt) {
            $ids = collect($lamaranIds)->map(fn ($id) => (int) $id)->values();
            if ($ids->count() !== $ids->unique()->count()) {
                throw ValidationException::withMessages(['selected_ids' => ['Kandidat tidak boleh dipilih lebih dari satu kali.']]);
            }

            if ($type !== AssessmentLink::TYPE_MCU) {
                $requiredStatus = $this->requiredLamaranStatusForType((string) $type);
                $eligibleIds = \App\Models\Lamaran::query()
                    ->lockForUpdate()
                    ->whereIn('id', $ids)
                    ->where('status_proses', $requiredStatus)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                foreach ($ids as $index => $id) {
                    if (!in_array($id, $eligibleIds, true)) {
                        throw ValidationException::withMessages(["selected_ids.$index" => ['Kandidat harus berada pada status '.$requiredStatus.'.']]);
                    }
                }

            }
            $schema = $this->schemaFor((string) $type, $fields, $eligibilityFieldId);

            $link = AssessmentLink::create([
                'assessment_type' => $type,
                'public_token' => $this->uniquePublicToken(),
                'pin_hash' => Hash::make($pin),
                'pin_encrypted' => $pin,
                'form_schema' => $schema,
                'created_by' => $creatorId,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]);

            foreach ($ids as $lamaranId) {
                AssessmentLinkCandidate::create([
                    'assessment_link_id' => $link->id,
                    'lamaran_id' => $lamaranId,
                ]);
            }

            return $link->load('candidates');
        });
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @return array<int, array<string, mixed>>
     */
    public function schemaFor(string $type, array $fields, ?string $eligibilityFieldId = null): array
    {
        if ($type === AssessmentLink::TYPE_MCU) {
            return [];
        }
        if (!in_array($type, ['kesehatan', 'lapangan'], true)) {
            throw ValidationException::withMessages(['assessment_type' => ['Tipe asesmen harus kesehatan atau lapangan.']]);
        }

        if (count($fields) > 20) {
            throw ValidationException::withMessages(['fields' => ['Maksimal 20 field tambahan.']]);
        }

        $schema = [];
        $fieldIds = [];
        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                throw ValidationException::withMessages(["fields.$index" => ['Format field tidak valid.']]);
            }

            $id = $field['id'] ?? null;
            $label = $field['label'] ?? null;
            $fieldType = $field['type'] ?? null;
            if (!is_string($id) || trim($id) === '' || strlen($id) > 100 || $id === 'petugas_note') {
                throw ValidationException::withMessages(["fields.$index.id" => ['ID field tidak valid.']]);
            }
            if (!is_string($label) || trim($label) === '' || strlen($label) > 100) {
                throw ValidationException::withMessages(["fields.$index.label" => ['Label wajib diisi dan maksimal 100 karakter.']]);
            }
            if (!in_array($fieldType, self::ALLOWED_TYPES, true)) {
                throw ValidationException::withMessages(["fields.$index.type" => ['Tipe field tidak valid.']]);
            }
            if (isset($fieldIds[$id]) || ($type === 'kesehatan' && $id === 'health_status')) {
                throw ValidationException::withMessages(["fields.$index.id" => ['ID field harus unik dan tidak boleh menimpa field standar.']]);
            }

            $normalized = [
                'id' => $id,
                'label' => $label,
                'type' => $fieldType,
                'required' => (bool) ($field['required'] ?? false),
            ];
            if ($fieldType === 'select') {
                $options = $field['options'] ?? [];
                if (!is_array($options) || count($options) === 0 || count($options) > 20) {
                    throw ValidationException::withMessages(["fields.$index.options" => ['Pilihan select harus berjumlah 1 sampai 20.']]);
                }
                foreach ($options as $optionIndex => $option) {
                    if (!is_string($option) || trim($option) === '' || strlen($option) > 100) {
                        throw ValidationException::withMessages(["fields.$index.options.$optionIndex" => ['Pilihan select tidak valid.']]);
                    }
                }
                $normalized['options'] = array_values($options);
            }

            $fieldIds[$id] = true;
            $schema[] = $normalized;
        }

        if ($type === 'kesehatan') {
            return array_merge([self::HEALTH_SCHEMA], $schema);
        }

        if (!is_string($eligibilityFieldId) || trim($eligibilityFieldId) === '') {
            throw ValidationException::withMessages(['eligibility_field_id' => ['Field penentu kelulusan wajib dipilih untuk tes lapangan.']]);
        }
        foreach ($schema as $index => $field) {
            if ($field['id'] !== $eligibilityFieldId) {
                continue;
            }
            if ($field['type'] !== 'select' || $field['options'] !== ['Lulus', 'Tidak Lulus']) {
                throw ValidationException::withMessages(['eligibility_field_id' => ['Field penentu kelulusan harus berupa pilihan Lulus dan Tidak Lulus.']]);
            }
            $schema[$index]['eligibility_decision_field'] = $eligibilityFieldId;

            return $schema;
        }

        throw ValidationException::withMessages(['eligibility_field_id' => ['Field penentu kelulusan tidak ditemukan pada form.']]);
    }

    public function eligibilityStatus(AssessmentLinkCandidate $candidate): string
    {
        $link = $candidate->assessmentLink;
        $values = (array) $candidate->result_values;
        if ($link === null) {
            return 'pending';
        }
        if ($link->assessment_type === 'kesehatan') {
            return $this->statusFromDecision($values['health_status'] ?? null, 'Sehat', 'Tidak Sehat');
        }
        foreach ((array) $link->form_schema as $field) {
            if (($field['eligibility_decision_field'] ?? null) === ($field['id'] ?? null)) {
                return $this->statusFromDecision($values[$field['id']] ?? null, 'Lulus', 'Tidak Lulus');
            }
        }

        return 'pending';
    }

    public function verifyPin(AssessmentLink $link, string $pin): bool
    {
        return Hash::check($pin, $link->pin_hash);
    }

    public function requiredLamaranStatus(AssessmentLink $link): string
    {
        return $this->requiredLamaranStatusForType($link->assessment_type);
    }

    /** @param array<int, int|string> $lamaranIds */
    public function addCandidates(AssessmentLink $link, array $lamaranIds): void
    {
        if ($link->isDocumentOnly()) {
            throw ValidationException::withMessages(['lamaran_ids' => ['Link Hasil MCU hanya untuk pengiriman dokumen, tanpa kandidat.']]);
        }
        DB::transaction(function () use ($link, $lamaranIds) {
            $lockedLink = AssessmentLink::query()->lockForUpdate()->findOrFail($link->id);
            $ids = collect($lamaranIds)->map(fn ($id) => (int) $id)->unique()->values();
            $existingIds = AssessmentLinkCandidate::query()
                ->where('assessment_link_id', $lockedLink->id)
                ->whereIn('lamaran_id', $ids)
                ->pluck('lamaran_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $eligibleIds = \App\Models\Lamaran::query()
                ->whereIn('id', $ids)
                ->where('status_proses', $this->requiredLamaranStatus($lockedLink))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($ids as $index => $id) {
                if (in_array($id, $existingIds, true)) {
                    throw ValidationException::withMessages(["lamaran_ids.$index" => ['Kandidat sudah ada pada link asesmen ini.']]);
                }
                if (!in_array($id, $eligibleIds, true)) {
                    throw ValidationException::withMessages(["lamaran_ids.$index" => ['Kandidat harus berada pada status '.$this->requiredLamaranStatus($lockedLink).'.']]);
                }
            }

            foreach ($ids as $id) {
                AssessmentLinkCandidate::create([
                    'assessment_link_id' => $lockedLink->id,
                    'lamaran_id' => $id,
                ]);
            }
        });
    }

    /**
     * @param array<string, mixed> $values
     */
    public function saveResult(AssessmentLinkCandidate $candidate, array $values, ?string $note, Request $request): AssessmentLinkCandidate
    {
        return DB::transaction(function () use ($candidate, $values, $note, $request) {
            $lockedCandidate = AssessmentLinkCandidate::query()
                ->lockForUpdate()
                ->find($candidate->id);

            if ($lockedCandidate === null) {
                throw (new ModelNotFoundException())->setModel(AssessmentLinkCandidate::class, [$candidate->id]);
            }

            $lockedLink = AssessmentLink::query()
                ->lockForUpdate()
                ->find($lockedCandidate->assessment_link_id);

            if ($lockedLink === null) {
                throw (new ModelNotFoundException())->setModel(AssessmentLinkCandidate::class, [$candidate->id]);
            }

            $this->ensureAccessible($lockedLink);
            $this->validateResult($lockedLink->form_schema, $values, $note);

            $oldValues = (array) ($lockedCandidate->result_values ?? []);
            $oldValues['petugas_note'] = $lockedCandidate->petugas_note;
            $newValues = $values;
            $newValues['petugas_note'] = $note;

            $lockedCandidate->update([
                'result_values' => $values,
                'petugas_note' => $note,
                'last_submitted_at' => now('Asia/Makassar'),
            ]);

            AssessmentResultAudit::create([
                'assessment_link_candidate_id' => $lockedCandidate->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);

            return $lockedCandidate->fresh();
        });
    }

    /** @param array<int, array<string, mixed>> $schema */
    private function validateResult(array $schema, array $values, ?string $note): void
    {
        if ($note !== null && strlen($note) > 2000) {
            throw ValidationException::withMessages(['note' => ['Catatan maksimal 2.000 karakter.']]);
        }

        $fields = [];
        foreach ($schema as $field) {
            $fields[$field['id']] = $field;
        }

        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $fields)) {
                throw ValidationException::withMessages(["values.$key" => ['Field hasil tidak dikenal.']]);
            }
        }

        foreach ($fields as $id => $field) {
            $value = $values[$id] ?? null;
            $blank = $value === null || (is_string($value) && trim($value) === '');
            if (($field['required'] ?? false) && $blank) {
                throw ValidationException::withMessages(["values.$id" => ['Field wajib diisi.']]);
            }
            if ($blank) {
                continue;
            }
            if ($field['type'] === 'select' && (!is_string($value) || !in_array($value, $field['options'], true))) {
                throw ValidationException::withMessages(["values.$id" => ['Pilihan hasil tidak valid.']]);
            }
            if ($field['type'] === 'number' && (!is_numeric($value) || is_array($value))) {
                throw ValidationException::withMessages(["values.$id" => ['Nilai harus berupa angka.']]);
            }
            if ($field['type'] === 'text' && (!is_string($value) || strlen($value) > 2000)) {
                throw ValidationException::withMessages(["values.$id" => ['Teks maksimal 2.000 karakter.']]);
            }
        }
    }

    private function uniquePublicToken(): string
    {
        do {
            $token = Str::random(64);
        } while (AssessmentLink::query()->where('public_token', $token)->exists());

        return $token;
    }

    private function requiredLamaranStatusForType(string $type): string
    {
        if ($type === 'kesehatan') {
            return 'Tes Kesehatan';
        }

        if ($type === 'lapangan') {
            return 'Tes Lapangan';
        }

        throw ValidationException::withMessages(['assessment_type' => ['Tipe asesmen harus kesehatan atau lapangan.']]);
    }

    private function ensureAccessible(AssessmentLink $link): void
    {
        if (!$link->isAccessibleAt(now('Asia/Makassar'))) {
            throw (new ModelNotFoundException())->setModel(AssessmentLink::class, [$link->id]);
        }
    }

    private function statusFromDecision($value, string $eligibleValue, string $ineligibleValue): string
    {
        if ($value === $eligibleValue) {
            return 'eligible';
        }
        if ($value === $ineligibleValue) {
            return 'ineligible';
        }

        return 'pending';
    }
}
