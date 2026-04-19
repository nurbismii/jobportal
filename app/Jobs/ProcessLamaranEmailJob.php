<?php

namespace App\Jobs;

use App\Models\EmailBlastLog;
use App\Models\Lamaran;
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
        if ($this->blast !== 'iya') {
            return;
        }

        Lamaran::with('biodata:id,user_id')
            ->whereIn('id', $this->ids)
            ->get()
            ->each(function ($lamaran) {

                if (!$lamaran->biodata) return;

                $userId = $lamaran->biodata->user_id;

                $this->dispatchEmail($userId, $lamaran->id);
            });
    }

    private function dispatchEmail($userId, $lamaranId)
    {
        $limitPerHour = 20;
        $delayPerEmail = 35; // lebih cepat dari 120 detik

        $sentLastHour = EmailBlastLog::where('created_at', '>=', now()->subHour())->count();

        if ($sentLastHour >= $limitPerHour) {

            // jika limit tercapai, kirim di jam berikutnya
            $nextHour = now()->addHour()->startOfHour();
            $delaySeconds = now()->diffInSeconds($nextHour) + rand(60, 180);
        } else {

            // delay normal antar email
            $delaySeconds = ($sentLastHour * $delayPerEmail) + rand(5, 15);
        }

        $batchKe = floor($sentLastHour / $limitPerHour) + 1;

        $log = EmailBlastLog::create([
            'user_id' => $userId,
            'lamaran_id' => $lamaranId,
            'status_proses' => $this->status,
            'batch_ke' => $batchKe,
            'delay_jam' => $batchKe - 1,
            'status_kirim' => 'pending'
        ]);

        SendProsesLamaranEmail::dispatch(
            $userId,
            $this->status,
            $lamaranId,
            $log->id,
            $this->pesan
        )->delay(now()->addSeconds($delaySeconds));
    }

}
