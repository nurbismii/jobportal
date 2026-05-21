<?php

namespace App\Services\Vhire;

use App\Jobs\SyncOnboardingCandidateToHris;
use App\Models\Lamaran;
use App\Models\VhireOnboardingCandidate;
use Carbon\Carbon;

class OnboardingCandidateSyncService
{
    public function prepareFromLamaran(Lamaran $lamaran, $tanggalMulaiKerja = null, array $options = []): ?VhireOnboardingCandidate
    {
        $lamaran->loadMissing('biodata.user', 'lowongan.permintaanTenagaKerja.departemen', 'lowongan.permintaanTenagaKerja.divisi');

        $biodata = $lamaran->biodata;
        $user = $biodata ? $biodata->user : null;
        $lowongan = $lamaran->lowongan;
        $ptk = $lowongan ? $lowongan->permintaanTenagaKerja : null;
        $noKtp = preg_replace('/\D+/', '', (string) ($biodata->no_ktp ?? $user->no_ktp ?? ''));

        if (! preg_match('/^\d{16}$/', $noKtp)) {
            app(VhireAuditLogger::class)->log('onboarding_candidate_validation_failed', null, [], [], [
                'lamaran_id' => $lamaran->id,
                'no_ktp' => $noKtp,
                'message' => 'No KTP harus 16 digit numerik.',
            ], 'vhire');

            return null;
        }

        $setting = app(PkwtContractSettingService::class)->pkwt1();
        $signingMethod = $options['signing_method'] ?? $setting->default_signing_method;

        if (! in_array($signingMethod, ['electronic', 'manual'], true)) {
            $signingMethod = 'electronic';
        }

        $candidateCode = 'VDNI/HRD/' . $lamaran->id;
        $vhireCandidateId = 'LAMARAN-' . $lamaran->id;
        $basePayload = [
            'vhire_candidate_id' => $vhireCandidateId,
            'candidate_code' => $candidateCode,
            'no_ktp' => $noKtp,
            'nama' => trim((string) ($user->name ?? $biodata->nama ?? '')),
            'jabatan' => $this->stringOrNull($ptk->posisi ?? $lowongan->nama_lowongan ?? null),
            'tanggal_mulai_kerja' => $tanggalMulaiKerja ? date('Y-m-d', strtotime((string) $tanggalMulaiKerja)) : null,
            'departemen' => $this->stringOrNull(optional(optional($ptk)->departemen)->departemen),
            'departemen_id' => $this->integerOrNull(optional($ptk)->departemen_id),
            'divisi' => $this->stringOrNull(optional(optional($ptk)->divisi)->nama_divisi),
            'divisi_id' => $this->integerOrNull(optional($ptk)->divisi_id),
            'lokasi' => $this->stringOrNull($user->area_kerja ?? null),
            'recruitment_status' => 'proses_tanda_tangan_kontrak',
            'onboarding_status' => 'draft',
            'contract_duration_value' => (int) $setting->duration_value,
            'contract_duration_unit' => (string) $setting->duration_unit,
            'signing_method' => $signingMethod,
        ];
        $payload = $this->compactPayload(array_merge(
            $basePayload,
            $this->employeeProfilePayload($biodata, $user)
        ));

        $candidate = VhireOnboardingCandidate::where('lamaran_id', $lamaran->id)->first();
        $old = $candidate ? $candidate->toArray() : [];

        $candidate = VhireOnboardingCandidate::updateOrCreate(
            ['lamaran_id' => $lamaran->id],
            [
                'biodata_id' => $biodata ? $biodata->id : null,
                'user_id' => $user ? $user->id : null,
                'vhire_candidate_id' => $vhireCandidateId,
                'candidate_code' => $candidateCode,
                'no_ktp' => $noKtp,
                'nama' => $basePayload['nama'],
                'jabatan' => $basePayload['jabatan'],
                'tanggal_mulai_kerja' => $basePayload['tanggal_mulai_kerja'],
                'departemen' => $basePayload['departemen'],
                'lokasi' => $basePayload['lokasi'],
                'recruitment_status' => $basePayload['recruitment_status'],
                'onboarding_status' => $basePayload['onboarding_status'],
                'contract_duration_value' => $basePayload['contract_duration_value'],
                'contract_duration_unit' => $basePayload['contract_duration_unit'],
                'signing_method' => $basePayload['signing_method'],
                'payload' => $payload,
                'sync_status' => 'draft',
                'last_sync_error' => null,
            ]
        );

        app(VhireAuditLogger::class)->log(
            'onboarding_candidate_prepared',
            $candidate,
            $old,
            $candidate->toArray(),
            ['lamaran_id' => $lamaran->id],
            'vhire'
        );

        SyncOnboardingCandidateToHris::dispatch($candidate->id)
            ->onQueue((string) config('recruitment.hris_api.queue', 'default'));

        return $candidate;
    }

    public function payload(VhireOnboardingCandidate $candidate): array
    {
        $candidate->loadMissing(
            'biodata.user',
            'user',
            'lamaran.lowongan.permintaanTenagaKerja.departemen',
            'lamaran.lowongan.permintaanTenagaKerja.divisi'
        );
        $storedPayload = is_array($candidate->payload) ? $candidate->payload : [];
        $user = $candidate->user ?: optional($candidate->biodata)->user;
        $workPayload = $this->workPayloadFromCandidate($candidate);

        return $this->compactPayload(array_merge($storedPayload, $this->employeeProfilePayload($candidate->biodata, $user), $workPayload, [
            'vhire_candidate_id' => $candidate->vhire_candidate_id,
            'candidate_code' => $candidate->candidate_code,
            'no_ktp' => $candidate->no_ktp,
            'nama' => $candidate->nama,
            'jabatan' => $candidate->jabatan ?: ($workPayload['jabatan'] ?? null),
            'tanggal_mulai_kerja' => optional($candidate->tanggal_mulai_kerja)->format('Y-m-d'),
            'departemen' => $candidate->departemen ?: ($workPayload['departemen'] ?? null),
            'departemen_id' => $workPayload['departemen_id'] ?? ($storedPayload['departemen_id'] ?? null),
            'divisi' => $workPayload['divisi'] ?? ($storedPayload['divisi'] ?? null),
            'divisi_id' => $workPayload['divisi_id'] ?? ($storedPayload['divisi_id'] ?? null),
            'lokasi' => $candidate->lokasi,
            'recruitment_status' => $candidate->recruitment_status,
            'onboarding_status' => $candidate->onboarding_status,
            'contract_duration_value' => (int) $candidate->contract_duration_value,
            'contract_duration_unit' => $candidate->contract_duration_unit,
            'signing_method' => $candidate->signing_method,
            'source_updated_at' => optional($candidate->updated_at)->toIso8601String(),
        ]));
    }

    private function stringOrNull($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function employeeProfilePayload($biodata, $user): array
    {
        if (! $biodata && ! $user) {
            return [];
        }

        return [
            'jenis_kelamin' => $this->stringOrNull(optional($biodata)->jenis_kelamin),
            'status_pernikahan' => $this->stringOrNull(optional($biodata)->status_pernikahan),
            'alamat' => $this->stringOrNull(optional($biodata)->alamat),
            'alamat_ktp' => $this->stringOrNull(optional($biodata)->alamat),
            'alamat_domisili' => $this->stringOrNull(optional($biodata)->alamat_domisili) ?: $this->stringOrNull(optional($biodata)->alamat),
            'provinsi_id' => $this->integerOrNull(optional($biodata)->provinsi),
            'kabupaten_id' => $this->integerOrNull(optional($biodata)->kabupaten),
            'kecamatan_id' => $this->integerOrNull(optional($biodata)->kecamatan),
            'kelurahan_id' => $this->integerOrNull(optional($biodata)->kelurahan),
            'nama_ibu_kandung' => $this->stringOrNull(optional($biodata)->nama_ibu),
            'nama_bapak' => $this->stringOrNull(optional($biodata)->nama_ayah),
            'nama_suami_atau_istri' => $this->stringOrNull(optional($biodata)->nama_ayah),
            'agama' => $this->stringOrNull(optional($biodata)->agama),
            'no_kk' => $this->digitsOrNull(optional($biodata)->no_kk),
            'kode_area_kerja' => $this->stringOrNull(optional($user)->area_kerja),
            'status_karyawan' => 'PKWT 合同工',
            'no_telp' => $this->stringOrNull(optional($biodata)->no_telp),
            'tanggal_lahir' => $this->dateOrNull(optional($biodata)->tanggal_lahir),
            'rt' => $this->stringOrNull(optional($biodata)->rt),
            'rw' => $this->stringOrNull(optional($biodata)->rw),
            'kode_pos' => $this->stringOrNull(optional($biodata)->kode_pos),
            'golongan_darah' => $this->stringOrNull(optional($biodata)->golongan_darah),
            'npwp' => $this->digitsOrNull(optional($biodata)->no_npwp),
            'tinggi' => $this->stringOrNull(optional($biodata)->tinggi_badan),
            'berat' => $this->stringOrNull(optional($biodata)->berat_badan),
            'hobi' => $this->stringOrNull(optional($biodata)->hobi),
            'nama_instansi_pendidikan' => $this->stringOrNull(optional($biodata)->nama_instansi),
            'pendidikan_terakhir' => $this->stringOrNull(optional($biodata)->pendidikan_terakhir),
            'jurusan' => $this->stringOrNull(optional($biodata)->jurusan),
            'tanggal_menikah' => $this->dateOrNull(optional($biodata)->tanggal_nikah),
        ];
    }

    private function workPayloadFromCandidate(VhireOnboardingCandidate $candidate): array
    {
        $lowongan = optional($candidate->lamaran)->lowongan;
        $ptk = optional($lowongan)->permintaanTenagaKerja;

        if (! $ptk) {
            return [];
        }

        return [
            'jabatan' => $this->stringOrNull($ptk->posisi ?? optional($lowongan)->nama_lowongan ?? null),
            'departemen' => $this->stringOrNull(optional(optional($ptk)->departemen)->departemen),
            'departemen_id' => $this->integerOrNull(optional($ptk)->departemen_id),
            'divisi' => $this->stringOrNull(optional(optional($ptk)->divisi)->nama_divisi),
            'divisi_id' => $this->integerOrNull(optional($ptk)->divisi_id),
        ];
    }

    private function digitsOrNull($value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }

    private function integerOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function dateOrNull($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function compactPayload(array $payload): array
    {
        return array_filter($payload, fn($value) => $value !== null && $value !== '');
    }
}
