<?php

namespace App\Services;

use App\Models\AssessmentLink;
use App\Models\AssessmentLinkCandidate;
use App\Models\AssessmentResultAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FieldTestService
{
    public const ROLES = ['assessor_1' => 'Penilai 1', 'assessor_2' => 'Penilai 2', 'division_head' => 'Penanggung jawab divisi'];

    public function templates(): array
    {
        $safety = ['Kesadaran keselamatan', 'Kecepatan reaksi', 'Prosedur operasi yang aman', 'Penanganan darurat: longsor atau kerusakan mesin'];
        $maintenance = ['Pemeriksaan rutin oli hidrolik, filter, track dan komponen lain', 'Pemeriksaan kebocoran oli dan suara abnormal'];
        $driving = ['Memeriksa sekeliling sebelum menyalakan alat', 'Membunyikan klakson sebelum menyalakan alat', 'Mengganti gigi', 'Pedal gas', 'Pengoperasian rem'];
        $lifting = ['Meletakkan objek pada lokasi tetap', 'Memindahkan objek melewati rintangan', 'Menjaga kestabilan objek', 'Mematikan mesin', 'Mundur masuk gudang', 'Pengoperasian klakson'];
        return [
            'dump_truck' => ['name' => 'Dump Truck', 'sections' => [
                'p2h' => ['label' => 'Pemeriksaan harian (P2H)', 'max' => null, 'indicators' => ['Ban', 'Spion', 'Tekanan angin', 'Air radiator', 'Oli mesin', 'Rem dan kopling']],
                'driving' => ['label' => 'Pengoperasian maju / mundur', 'max' => null, 'indicators' => ['Pengoperasian rem tangan sesuai prosedur', 'Pengoperasian kopling sesuai prosedur', 'Melewati drum tanpa menabrak', 'Mengikuti jalur tes dengan benar', 'Melaju tanpa berhenti di tengah jalur', 'Pengoperasian klakson sesuai prosedur']],
                'dump' => ['label' => 'Pengoperasian dump', 'max' => null, 'indicators' => ['Power Take Off (PTO)', 'Naik dump', 'Turun dump', 'Perseneling', 'Gas', 'Parkir']],
            ]],
            'excavator_loader' => ['name' => 'Excavator / Loader', 'sections' => [
                'p2h' => ['label' => 'Pemeriksaan harian (P2H)', 'max' => 20, 'indicators' => ['Ban', 'Spion samping', 'Spion dalam', 'Air radiator', 'Oli hidrolik', 'Oli swing / transmisi']],
                'driving' => ['label' => 'Keterampilan mengemudi', 'max' => 40, 'indicators' => array_merge($driving, ['Menurunkan bucket', 'Berjalan, berbelok, menggali dan meratakan', 'Tanjakan', 'Mematikan mesin', 'Mundur masuk gudang', 'Pengoperasian klakson'])],
                'safety' => ['label' => 'Standar keselamatan', 'max' => 20, 'indicators' => $safety],
                'maintenance' => ['label' => 'Pemeliharaan mesin', 'max' => 20, 'indicators' => $maintenance],
            ]],
            'mobile_crane' => ['name' => 'Mobile Crane', 'sections' => [
                'p2h' => ['label' => 'Pemeriksaan harian (P2H)', 'max' => 10, 'indicators' => ['Ban', 'Spion samping', 'Air radiator', 'Oli mesin', 'Tekanan udara']],
                'driving' => ['label' => 'Keterampilan mengemudi', 'max' => 60, 'indicators' => array_merge($driving, $lifting)],
                'safety' => ['label' => 'Standar keselamatan', 'max' => 20, 'indicators' => $safety],
                'maintenance' => ['label' => 'Pemeliharaan mesin', 'max' => 10, 'indicators' => $maintenance],
            ]],
            'trailer' => ['name' => 'Trailer', 'sections' => [
                'p2h' => ['label' => 'Pemeriksaan harian (P2H)', 'max' => 20, 'indicators' => ['Ban', 'Spion samping', 'Air radiator', 'Oli mesin', 'Tekanan udara', 'Oli hidrolik', 'U-bolt dan kabel penghubung gandengan']],
                'driving' => ['label' => 'Keterampilan mengemudi', 'max' => 40, 'indicators' => array_merge($driving, $lifting)],
                'safety' => ['label' => 'Standar keselamatan', 'max' => 20, 'indicators' => $safety],
                'maintenance' => ['label' => 'Pemeliharaan mesin', 'max' => 20, 'indicators' => $maintenance],
            ]],
        ];
    }

    public function configuration(array $input): array
    {
        $template = $this->templates()[$input['template'] ?? ''] ?? null;
        if (!$template) {
            throw ValidationException::withMessages(['field_test.template' => 'Pilih jenis alat yang tersedia.']);
        }
        $rules = ['location' => 'required|string|max:200', 'test_date' => 'required|date_format:Y-m-d', 'signers' => 'required|array:assessor_1,assessor_2,division_head'];
        $labels = ['location' => 'Lokasi tes', 'test_date' => 'Tanggal tes', 'signers' => 'Penanda tangan'];
        foreach (self::ROLES as $role => $label) {
            $rules['signers.'.$role] = ['required', 'array:name'];
            $rules['signers.'.$role.'.name'] = ['required', 'string', 'max:120'];
            $labels['signers.'.$role] = $label;
            $labels['signers.'.$role.'.name'] = 'Nama '.$label;
        }
        foreach ($template['sections'] as $id => $section) {
            $rules['maxima.'.$id] = 'required|integer|min:1|max:1000';
        }
        validator($input, $rules, ['required' => ':attribute wajib diisi.', 'array' => 'Data :attribute tidak valid. Isi nama petugas.'], $labels)->validate();
        $signers = [];
        foreach (self::ROLES as $role => $label) {
            $signers[$role] = ['name' => preg_replace('/\s+/u', ' ', trim($input['signers'][$role]['name']))];
        }
        if (count(array_unique(array_map(fn ($signer) => mb_strtolower($signer['name']), $signers))) !== 3) {
            throw ValidationException::withMessages(['field_test.signers' => 'Tiga penanda tangan harus orang yang berbeda.']);
        }
        foreach ($template['sections'] as $id => &$section) {
            $section['max'] = (int) $input['maxima'][$id];
        }
        unset($section);
        return $template + ['scoring' => 'yes_no_v1', 'template' => $input['template'], 'location' => $input['location'], 'test_date' => $input['test_date'], 'signers' => $signers];
    }

    public function sectionsFor(AssessmentLink $link, array $values = []): array
    {
        if (isset($values['component_sections'])) {
            return $values['component_sections'];
        }
        $config = $link->field_test_config;
        $sections = $config['sections'];
        if (($config['scoring'] ?? null) !== 'yes_no_v1') {
            // Old links keep their weights; unsigned results use the corrected component list.
            foreach ($sections as $id => &$section) {
                $section['indicators'] = $this->templates()[$config['template']]['sections'][$id]['indicators'];
            }
            unset($section);
        }
        return $sections;
    }

    public function schema(array $config): array
    {
        $schema = [];
        foreach ($config['sections'] as $id => $section) {
            $schema[] = ['id' => $id, 'label' => $section['label'], 'type' => 'number', 'required' => true];
        }
        $schema[] = ['id' => 'decision', 'label' => 'Hasil tes', 'type' => 'select', 'required' => true, 'options' => ['Lulus', 'Tidak Lulus'], 'eligibility_decision_field' => 'decision'];
        return $schema;
    }

    public function save(AssessmentLink $link, int $id, array $input, Request $request): AssessmentLinkCandidate
    {
        return DB::transaction(function () use ($link, $id, $input, $request) {
            $candidate = $this->lockedCandidate($link, $id);
            $this->checkVersion($candidate, $input);
            abort_if(count($candidate->field_test_signatures ?? []) > 0, 409, 'Nilai sudah ditandatangani. Kembalikan untuk perbaikan terlebih dahulu.');
            $sections = $this->sectionsFor($link, $candidate->result_values ?? []);
            $values = $this->validatedChecklistValues($input['values'], $sections, false);
            $before = $this->snapshot($candidate);
            $candidate->update(['result_values' => $values, 'petugas_note' => $input['petugas_note'] ?? null, 'field_test_version' => $candidate->field_test_version + 1]);
            $this->audit($candidate, $before, 'draft_saved', $request);
            return $candidate;
        });
    }

    public function sign(AssessmentLink $link, int $id, array $input, Request $request): AssessmentLinkCandidate
    {
        $storedPath = null;
        try {
            return DB::transaction(function () use ($link, $id, $input, $request, &$storedPath) {
                $candidate = $this->lockedCandidate($link, $id);
                $signerName = $this->signerName($link, $input['role']);
                abort_unless((int) $input['revision'] === $candidate->field_test_revision, 409, 'Formulir sudah direvisi. Muat ulang untuk melihat nilai terbaru.');
                $signatures = $candidate->field_test_signatures ?? [];
                if (isset($signatures[$input['role']])) {
                    return $candidate;
                }
                $this->checkVersion($candidate, $input);
                $expected = array_keys(self::ROLES)[count($signatures)] ?? null;
                abort_unless($expected === $input['role'], 409, 'Selesaikan tanda tangan sebelumnya terlebih dahulu.');
                $values = $candidate->result_values ?? [];
                if (array_key_exists('components', $values)) {
                    $this->validatedChecklistValues($values, $this->sectionsFor($link, $values), true);
                } else {
                    abort_unless(count($signatures) > 0, 422, 'Lengkapi pilihan Ya/Tidak pada setiap komponen sebelum menandatangani.');
                    $this->validatedValues($link, $values, true);
                }
                if ($input['role'] === 'assessor_1') {
                    $reason = trim((string) $candidate->petugas_note);
                    if (($candidate->result_values['decision'] ?? '') === 'Tidak Lulus' && $reason === '') {
                        throw ValidationException::withMessages(['petugas_note' => 'Tuliskan alasan kandidat tidak lulus.']);
                    }
                }
                $bytes = $this->signatureBytes($input['signature_data']);
                $before = $this->snapshot($candidate);
                $storedPath = 'field-test-signatures/'.$candidate->id.'/'.Str::uuid().'.png';
                Storage::disk('assessment_private')->put($storedPath, $bytes);
                $signatures[$input['role']] = [
                    'user_id' => null, 'name' => $signerName, 'signed_at' => now()->toIso8601String(),
                    'revision' => $candidate->field_test_revision, 'path' => $storedPath,
                    'file_hash' => hash('sha256', $bytes), 'result_hash' => hash('sha256', json_encode($before['values'], JSON_UNESCAPED_UNICODE).'|'.$candidate->petugas_note),
                ];
                $candidate->update(['field_test_signatures' => $signatures, 'field_test_version' => $candidate->field_test_version + 1, 'last_submitted_at' => count($signatures) === 3 ? now() : null]);
                $this->audit($candidate, $before, 'signed_'.$input['role'], $request);
                return $candidate;
            });
        } catch (\Throwable $e) {
            if ($storedPath) {
                Storage::disk('assessment_private')->delete($storedPath);
            }
            throw $e;
        }
    }

    public function revise(AssessmentLink $link, int $id, array $input, Request $request): AssessmentLinkCandidate
    {
        return DB::transaction(function () use ($link, $id, $input, $request) {
            $candidate = $this->lockedCandidate($link, $id);
            $signerName = $this->signerName($link, $input['role']);
            $this->checkVersion($candidate, $input);
            $before = $this->snapshot($candidate);
            $candidate->update(['field_test_signatures' => [], 'last_submitted_at' => null, 'field_test_revision' => $candidate->field_test_revision + 1, 'field_test_version' => $candidate->field_test_version + 1]);
            $this->audit($candidate, $before, 'revision_requested', $request, ['reason' => $input['reason'], 'requested_by' => $signerName]);
            return $candidate;
        });
    }

    private function lockedCandidate(AssessmentLink $link, int $id): AssessmentLinkCandidate
    {
        $locked = AssessmentLink::query()->lockForUpdate()->findOrFail($link->id);
        abort_unless($locked->isFieldTest() && $locked->isAccessibleAt(now()), 404);
        return $locked->candidates()->lockForUpdate()->findOrFail($id);
    }

    private function checkVersion(AssessmentLinkCandidate $candidate, array $input): void
    {
        abort_unless((int) $input['version'] === $candidate->field_test_version, 409, 'Ada perubahan dari perangkat lain. Muat ulang sebelum melanjutkan; salin isian Anda terlebih dahulu.');
    }

    private function signerName(AssessmentLink $link, string $role): string
    {
        $name = $link->field_test_config['signers'][$role]['name'] ?? null;
        abort_unless(is_string($name) && trim($name) !== '', 422, 'Nama penanda tangan belum ditetapkan. Hubungi HR.');
        return $name;
    }

    private function validatedValues(AssessmentLink $link, array $values, bool $complete): array
    {
        $required = $complete ? 'required' : 'nullable';
        $rules = ['sim' => [$required, Rule::in(['Ada', 'Tidak Ada'])], 'ktp' => [$required, Rule::in(['Ada', 'Tidak Ada'])], 'decision' => [$required, Rule::in(['Lulus', 'Tidak Lulus'])]];
        foreach ($link->field_test_config['sections'] as $id => $section) {
            $rules[$id] = [$required, 'numeric', 'min:0', 'max:'.$section['max']];
        }
        validator(['values' => $values], ['values' => 'array:'.implode(',', array_keys($rules))], ['array' => 'Terdapat isian yang tidak dikenal pada formulir. Muat ulang formulir sebelum melanjutkan.'])->validate();
        $labels = ['sim' => 'SIM B2 Umum', 'ktp' => 'KTP', 'decision' => 'Hasil tes'];
        foreach ($link->field_test_config['sections'] as $id => $section) {
            $labels[$id] = 'Nilai '.$section['label'];
        }
        $validated = validator($values, $rules, ['required' => ':attribute wajib diisi sebelum tanda tangan.', 'numeric' => ':attribute harus berupa angka.', 'min' => ':attribute minimal :min.', 'max' => ':attribute maksimal :max.', 'in' => 'Pilihan :attribute tidak valid.'], $labels)->validate();
        foreach ($link->field_test_config['sections'] as $id => $section) {
            if (isset($validated[$id])) {
                $validated[$id] = (float) $validated[$id];
            }
        }
        return $validated;
    }

    private function validatedChecklistValues(array $values, array $sections, bool $complete): array
    {
        $required = $complete ? 'required' : 'nullable';
        $allowed = ['sim', 'ktp', 'decision', 'components'];
        if ($complete) {
            $allowed = array_merge($allowed, array_keys($sections), ['component_sections']);
        }
        $rules = [
            'values' => 'array:'.implode(',', $allowed),
            'values.sim' => [$required, Rule::in(['Ada', 'Tidak Ada'])],
            'values.ktp' => [$required, Rule::in(['Ada', 'Tidak Ada'])],
            'values.decision' => [$required, Rule::in(['Lulus', 'Tidak Lulus'])],
            'values.components' => 'present|array:'.implode(',', array_keys($sections)),
        ];
        $labels = ['values' => 'Form penilaian', 'values.sim' => 'SIM B2 Umum', 'values.ktp' => 'KTP', 'values.decision' => 'Hasil tes', 'values.components' => 'Pilihan komponen'];
        foreach ($sections as $id => $section) {
            $rules['values.components.'.$id] = [$required, 'array:'.implode(',', array_keys($section['indicators']))];
            foreach ($section['indicators'] as $index => $label) {
                $field = 'values.components.'.$id.'.'.$index;
                $rules[$field] = [$required, Rule::in(['Ya', 'Tidak'])];
                $labels[$field] = $section['label'].' — '.$label;
            }
        }
        validator(['values' => $values], $rules, ['required' => ':attribute belum dijawab. Pilih Ya atau Tidak.', 'present' => ':attribute wajib dikirim.', 'array' => ':attribute berisi komponen yang tidak dikenal atau nilai manual. Muat ulang formulir.', 'in' => ':attribute harus dipilih sesuai pilihan yang tersedia.'], $labels)->validate();
        $result = array_intersect_key($values, array_flip(['sim', 'ktp', 'decision']));
        $result['components'] = [];
        foreach ($sections as $id => $section) {
            $answers = [];
            foreach ($section['indicators'] as $index => $label) {
                $answers[] = $values['components'][$id][$index] ?? null;
            }
            $result['components'][$id] = $answers;
            $answered = count(array_filter($answers, fn ($answer) => $answer !== null));
            $yes = count(array_filter($answers, fn ($answer) => $answer === 'Ya'));
            $result[$id] = $answered ? round($yes * $section['max'] / count($answers), 2) : null;
        }
        $result['component_sections'] = $sections;
        return $result;
    }

    private function signatureBytes(string $data): string
    {
        $bytes = str_starts_with($data, 'data:image/png;base64,') ? base64_decode(substr($data, 22), true) : false;
        $size = $bytes ? @getimagesizefromstring($bytes) : false;
        if (!$size || $size[2] !== IMAGETYPE_PNG || $size[0] < 200 || $size[1] < 80 || $size[0] > 1600 || $size[1] > 800 || strlen($bytes) > 200000) {
            throw ValidationException::withMessages(['signature_data' => 'Tanda tangan tidak valid. Silakan gambar ulang di kotak tanda tangan.']);
        }
        $image = @imagecreatefromstring($bytes);
        $ink = 0;
        if ($image) {
            for ($y = 0; $y < $size[1] && $ink < 12; $y += 2) {
                for ($x = 0; $x < $size[0] && $ink < 12; $x += 2) {
                    $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                    if ($color['alpha'] < 100 && $color['red'] + $color['green'] + $color['blue'] < 650) {
                        $ink++;
                    }
                }
            }
        }
        if ($ink < 12) {
            throw ValidationException::withMessages(['signature_data' => 'Kotak tanda tangan masih kosong. Silakan tanda tangan terlebih dahulu.']);
        }
        return $bytes;
    }

    private function snapshot(AssessmentLinkCandidate $candidate): array
    {
        return ['values' => $candidate->result_values ?? [], 'petugas_note' => $candidate->petugas_note, 'revision' => $candidate->field_test_revision, 'signatures' => $candidate->field_test_signatures ?? []];
    }

    private function audit(AssessmentLinkCandidate $candidate, array $before, string $event, Request $request, array $extra = []): void
    {
        AssessmentResultAudit::create(['assessment_link_candidate_id' => $candidate->id, 'old_values' => $before, 'new_values' => $this->snapshot($candidate) + ['event' => $event] + $extra, 'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 255, '')]);
    }
}
