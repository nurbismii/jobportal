<?php

namespace App\Jobs;

use App\Models\EmailBlastLog;
use App\Models\Lamaran;
use App\Models\RiwayatProsesLamaran;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessLamaranEmailJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $ids, $status, $tanggal, $jam, $tempat, $pesan, $blast;

    public $timeout = 120;

    public function __construct($ids, $status, $tanggal, $jam, $tempat, $pesan, $blast)
    {
        $this->ids = $ids;
        $this->status = $status;
        $this->tanggal = $tanggal;
        $this->jam = $jam;
        $this->tempat = $tempat;
        $this->pesan = $pesan;
        $this->blast = $blast;
    }

    public function handle()
    {
        Lamaran::with('biodata:id,user_id', 'lowongan:id')
            ->whereIn('id', $this->ids)
            ->get()
            ->each(function ($lamaran) {

                if (!$lamaran->biodata) return;

                $userId = $lamaran->biodata->user_id;

                RiwayatProsesLamaran::firstOrCreate([
                    'user_id' => $userId,
                    'lamaran_id' => $lamaran->id,
                    'status_proses' => $this->status,
                ], [
                    'status_lolos' => $this->isTidakLolos($this->status) ? 'Tidak Lolos' : null,
                    'tanggal_proses' => $this->tanggal,
                    'jam' => $this->jam,
                    'tempat' => $this->tempat ?: '-',
                    'pesan' => $this->pesan ?: '-'
                ]);

                if ($this->blast === 'iya') {
                    $this->dispatchEmail($userId, $lamaran->id);
                }
            });
    }

    private function dispatchEmail($userId, $lamaranId)
    {
        $limitPerHour = 50;

        // Hitung email 1 jam terakhir
        $counter = EmailBlastLog::where('created_at', '>=', now()->subHour())->count();

        // Tentukan batch ke-
        $batchKe = floor($counter / $limitPerHour) + 1;

        // Hitung delay
        $delaySeconds = (($batchKe - 1) * 3600) + (($counter % $limitPerHour) * 70);

        // Simpan log
        $log = EmailBlastLog::create([
            'user_id' => $userId,
            'lamaran_id' => $lamaranId,
            'status_proses' => $this->status,
            'batch_ke' => $batchKe,
            'delay_jam' => $batchKe - 1,
            'status_kirim' => 'pending'
        ]);

        // Dispatch email job dengan delay
        SendProsesLamaranEmail::dispatch(
            $userId,
            $this->status,
            $lamaranId,
            $log->id,
            $this->pesan
        )->delay(now()->addSeconds($delaySeconds));
    }

    private function isTidakLolos($status)
    {
        return in_array(strtolower($status), [
            'belum sesuai kriteria',
            'tidak lolos verifikasi online',
            'tidak lolos verifikasi berkas',
            'tidak lolos tes kesehatan',
            'tidak lolos tes lapangan',
            'tidak lolos medical check-up',
            'tidak lolos induksi safety',
            'tidak lolos tanda tangan kontrak',
            'tidak tanda tangan kontrak'
        ]);
    }
}
