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
        $fields = $attributes['fields'] ?? $attributes['form_schema'] ?? [];
        $schema = $this->schemaFor((string) $type, is_array($fields) ? $fields : []);
        $pin = $attributes['pin'] ?? null;

        if (!is_string($pin) || trim($pin) === '' || strlen($pin) > 255) {
            throw ValidationException::withMessages(['pin' => ['PIN wajib diisi dan maksimal 255 karakter.']]);
        }

        return DB::transaction(function () use ($type, $schema, $pin, $creatorId, $lamaranIds) {
            $link = AssessmentLink::create([
                'assessment_type' => $type,
                'public_token' => $this->uniquePublicToken(),
                'pin_hash' => Hash::make($pin),
                'form_schema' => $schema,
                'created_by' => $creatorId,
                'expires_at' => now('Asia/Makassar')->endOfDay(),
                'is_active' => true,
            ]);

            foreach (array_unique($lamaranIds, SORT_REGULAR) as $lamaranId) {
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
    public function schemaFor(string $type, array $fields): array
    {
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

        return $type === 'kesehatan'
            ? array_merge([self::HEALTH_SCHEMA], $schema)
            : $schema;
    }

    public function verifyPin(AssessmentLink $link, string $pin): bool
    {
        return Hash::check($pin, $link->pin_hash);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function saveResult(AssessmentLinkCandidate $candidate, array $values, ?string $note, Request $request): AssessmentLinkCandidate
    {
        return DB::transaction(function () use ($candidate, $values, $note, $request) {
            $lockedCandidate = AssessmentLinkCandidate::query()
                ->lockForUpdate()
                ->with('assessmentLink')
                ->find($candidate->id);

            if ($lockedCandidate === null || $lockedCandidate->assessmentLink === null || !$lockedCandidate->assessmentLink->isAccessibleAt(now('Asia/Makassar'))) {
                throw (new ModelNotFoundException())->setModel(AssessmentLinkCandidate::class, [$candidate->id]);
            }

            $this->validateResult($lockedCandidate->assessmentLink->form_schema, $values, $note);

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
}
