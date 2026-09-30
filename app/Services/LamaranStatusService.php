<?php

namespace App\Services;

use App\Models\Lamaran;
use App\Models\PermintaanTenagaKerja;
use App\Models\RiwayatProsesLamaran;
use Illuminate\Validation\ValidationException;

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
        $user = $biodata?->user()->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'biodata' => "Lamaran ID {$lamaran->id} tidak memiliki biodata dengan akun user yang valid. Pulihkan relasi akun sebelum memperbarui status.",
            ]);
        }

        $userId = $user->id;

        return $lamaran->getConnection()->transaction(function () use ($lamaran, $statusLolos, $statusProses, $userId, $tanggalProses, $jam, $tempat, $pesan, $statusTahapan, $user) {
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
                $user->markAsActiveEmployee($tanggalProses);

                $lowongan = $lamaran->lowongan;
                $permintaanTenagaKerjaId = $lowongan ? $lowongan->permintaan_tenaga_kerja_id : null;

                if ($permintaanTenagaKerjaId) {
                    PermintaanTenagaKerja::syncJumlahMasukById($permintaanTenagaKerjaId);
                }
            }

            return $riwayat;
        });
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
