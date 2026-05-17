<?php

namespace App\Services;

use App\Models\Lamaran;
use App\Models\PermintaanTenagaKerja;
use App\Models\RiwayatProsesLamaran;
use App\Models\User;
use App\Services\Vhire\OnboardingCandidateSyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LamaranStatusService
{
    public function apply(
        Lamaran $lamaran,
        string $status,
        $tanggalProses = null,
        ?string $jam = null,
        ?string $tempat = null,
        ?string $pesan = null,
        array $options = []
    ): RiwayatProsesLamaran {
        $statusTahapan = strtolower(trim($status));
        $statusProses = ucwords(trim($status));
        $statusLolos = $this->isTidakLolos($statusTahapan) ? 'Tidak Lolos' : null;
        $biodata = $lamaran->biodata;
        $userId = $biodata ? $biodata->user_id : null;

        $lamaran->update([
            'status_lamaran' => $statusLolos === 'Tidak Lolos' ? 0 : 1,
            'status_proses' => $statusProses,
        ]);

        $riwayat = RiwayatProsesLamaran::updateOrCreate([
            'user_id' => $userId,
            'lamaran_id' => $lamaran->id,
            'status_proses' => $statusProses,
        ], [
            'status_lolos' => $statusLolos,
            'tanggal_proses' => $tanggalProses,
            'jam' => filled($jam) ? $jam : now()->format('H:i:s'),
            'tempat' => filled($tempat) ? $tempat : '-',
            'pesan' => filled($pesan) ? $pesan : '-',
        ]);

        if ($statusTahapan === 'aktif bekerja') {
            $user = null;

            if ($biodata && $biodata->relationLoaded('user') && $biodata->user) {
                $user = $biodata->user;
            } elseif ($userId) {
                $user = User::find($userId);
            }

            if ($user) {
                $user->markAsActiveEmployee($tanggalProses);
            }

            $lowongan = $lamaran->lowongan;
            $permintaanTenagaKerjaId = $lowongan ? $lowongan->permintaan_tenaga_kerja_id : null;

            if ($permintaanTenagaKerjaId) {
                PermintaanTenagaKerja::syncJumlahMasukById($permintaanTenagaKerjaId);
            }
        }

        if ($this->isTandaTanganKontrak($statusTahapan)) {
            if ($this->supportsPkwtIntegrationTables()) {
                app(OnboardingCandidateSyncService::class)->prepareFromLamaran(
                    $lamaran,
                    $tanggalProses,
                    $options
                );
            } else {
                Log::warning('PKWT integration skipped because required tables are missing.', [
                    'lamaran_id' => $lamaran->id,
                    'status_proses' => $status,
                ]);
            }
        }

        return $riwayat;
    }

    private function supportsPkwtIntegrationTables(): bool
    {
        return Schema::hasTable('vhire_onboarding_candidates')
            && Schema::hasTable('vhire_pkwt_contracts')
            && Schema::hasTable('vhire_integration_audit_logs');
    }

    private function isTandaTanganKontrak(string $status): bool
    {
        return in_array($status, [
            'tanda tangan kontrak',
            'proses tanda tangan kontrak',
            'proses_tanda_tangan_kontrak',
        ], true);
    }

    private function isTidakLolos(string $status): bool
    {
        return in_array($status, [
            'belum sesuai kriteria',
            'tidak lolos verifikasi online',
            'tidak lolos verifikasi berkas',
            'tidak lolos tes kesehatan',
            'tidak lolos tes lapangan',
            'tidak lolos medical check-up',
            'tidak lolos induksi safety',
            'tidak lolos tanda tangan kontrak',
            'tidak tanda tangan kontrak',
            'tidak lolos',
            'belum sesuai',
        ], true);
    }
}
