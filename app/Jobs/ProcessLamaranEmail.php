<?php

namespace App\Jobs;

use App\Models\EmailBlastLog;
use App\Models\Lamaran;
use App\Models\RiwayatProsesLamaran;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessLamaranEmail implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $ids;
    public $status;
    public $tanggal;
    public $jam;
    public $tempat;
    public $pesan;
    public $blastEmail;

    public function __construct($ids, $status, $tanggal = null, $jam = null, $tempat = null, $pesan = null, $blastEmail = false)
    {
        $this->ids = $ids;
        $this->status = $status;
        $this->tanggal = $tanggal;
        $this->jam = $jam;
        $this->tempat = $tempat;
        $this->pesan = $pesan;
        $this->blastEmail = $blastEmail;
    }

    public function handle()
    {
        $limitPerJam = 50;
        $counter = 0;

        Lamaran::with('biodata', 'lowongan')
            ->whereIn('id', $this->ids)
            ->chunkById(100, function ($lamaranList) use (&$counter, $limitPerJam) {

                foreach ($lamaranList as $data) {

                    if (!$data->biodata) {
                        continue;
                    }

                    $userId = $data->biodata->user_id;
                    $lamaranId = $data->id;

                    // === SIMPAN RIWAYAT JIKA BELUM ADA ===
                    $exists = RiwayatProsesLamaran::where([
                        'user_id' => $userId,
                        'lamaran_id' => $lamaranId,
                        'status_proses' => $this->status,
                    ])->exists();

                    if (!$exists) {
                        RiwayatProsesLamaran::create([
                            'user_id' => $userId,
                            'lamaran_id' => $lamaranId,
                            'status_proses' => $this->status,
                            'status_lolos' => $this->isTidakLolos() ? 'Tidak Lolos' : null,
                            'tanggal_proses' => $this->tanggal,
                            'jam' => $this->jam,
                            'tempat' => $this->tempat ?: '-',
                            'pesan' => $this->pesan ?: '-'
                        ]);
                    }

                    // === EMAIL BLAST ===
                    if ($this->blastEmail === 'iya') {

                        $delayJam = floor($counter / $limitPerJam);

                        $log = EmailBlastLog::create([
                            'user_id' => $userId,
                            'lamaran_id' => $lamaranId,
                            'status_proses' => $this->status,
                            'batch_ke' => $delayJam + 1,
                            'delay_jam' => $delayJam,
                            'status_kirim' => 'pending'
                        ]);

                        SendProsesLamaranEmail::dispatch(
                            $userId,
                            $this->status,
                            $lamaranId,
                            $log->id,
                            $this->pesan
                        )->delay(now()->addHours($delayJam));

                        $counter++;
                    }
                }
            });
    }

    private function isTidakLolos()
    {
        return in_array(strtolower($this->status), array(
            'belum sesuai kriteria',
            'tidak lolos verifikasi online',
            'tidak lolos verifikasi berkas',
            'tidak lolos tes kesehatan',
            'tidak lolos tes lapangan',
            'tidak lolos medical check-up',
            'tidak lolos induksi safety',
            'tidak lolos tanda tangan kontrak',
            'tidak tanda tangan kontrak'
        ));
    }
}
