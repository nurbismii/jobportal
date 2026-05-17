<?php

namespace App\Services\Vhire;

use App\Jobs\SyncOnboardingCandidateToHris;
use App\Models\Lamaran;
use App\Models\VhireOnboardingCandidate;

class OnboardingCandidateSyncService
{
    public function prepareFromLamaran(Lamaran $lamaran, $tanggalMulaiKerja = null, array $options = []): ?VhireOnboardingCandidate
    {
        $lamaran->loadMissing('biodata.user', 'lowongan.permintaanTenagaKerja.departemen');

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
        $payload = [
            'vhire_candidate_id' => $vhireCandidateId,
            'candidate_code' => $candidateCode,
            'no_ktp' => $noKtp,
            'nama' => trim((string) ($user->name ?? $biodata->nama ?? '')),
            'jabatan' => $this->stringOrNull($ptk->posisi ?? $lowongan->nama_lowongan ?? null),
            'tanggal_mulai_kerja' => $tanggalMulaiKerja ? date('Y-m-d', strtotime((string) $tanggalMulaiKerja)) : null,
            'departemen' => $this->stringOrNull(optional($ptk->departemen ?? null)->departemen),
            'lokasi' => $this->stringOrNull($user->area_kerja ?? null),
            'recruitment_status' => 'proses_tanda_tangan_kontrak',
            'onboarding_status' => 'draft',
            'contract_duration_value' => (int) $setting->duration_value,
            'contract_duration_unit' => (string) $setting->duration_unit,
            'signing_method' => $signingMethod,
        ];

        $candidate = VhireOnboardingCandidate::where('lamaran_id', $lamaran->id)->first();
        $old = $candidate ? $candidate->toArray() : [];

        $candidate = VhireOnboardingCandidate::updateOrCreate(
            ['lamaran_id' => $lamaran->id],
            array_merge($payload, [
                'biodata_id' => $biodata ? $biodata->id : null,
                'user_id' => $user ? $user->id : null,
                'payload' => $payload,
                'sync_status' => 'draft',
                'last_sync_error' => null,
            ])
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
        return [
            'vhire_candidate_id' => $candidate->vhire_candidate_id,
            'candidate_code' => $candidate->candidate_code,
            'no_ktp' => $candidate->no_ktp,
            'nama' => $candidate->nama,
            'jabatan' => $candidate->jabatan,
            'tanggal_mulai_kerja' => optional($candidate->tanggal_mulai_kerja)->format('Y-m-d'),
            'departemen' => $candidate->departemen,
            'lokasi' => $candidate->lokasi,
            'recruitment_status' => $candidate->recruitment_status,
            'onboarding_status' => $candidate->onboarding_status,
            'contract_duration_value' => (int) $candidate->contract_duration_value,
            'contract_duration_unit' => $candidate->contract_duration_unit,
            'signing_method' => $candidate->signing_method,
            'source_updated_at' => optional($candidate->updated_at)->toIso8601String(),
        ];
    }

    private function stringOrNull($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
