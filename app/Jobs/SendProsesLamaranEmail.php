<?php

namespace App\Jobs;

use App\Models\EmailBlastLog;
use App\Models\Lamaran;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendProsesLamaranEmail implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels, InteractsWithQueue;

    public $userId;
    public $status;
    public $lamaranId;
    public $logId;
    public $pesan;

    public function __construct($userId, $status, $lamaranId, $logId, $pesan)
    {
        $this->userId = $userId;
        $this->status = $status;
        $this->lamaranId = $lamaranId;
        $this->logId = $logId;
        $this->pesan = $pesan;
    }

    public function handle()
    {
        try {
            $user = User::find($this->userId);
            $lamaran = Lamaran::with('lowongan')->find($this->lamaranId);

            if (!$user || !$lamaran) {
                EmailBlastLog::where('id', $this->logId)->update([
                    'status_kirim' => 'gagal_data_hilang'
                ]);
                return;
            }

            Mail::to($user->email)
                ->send(new \App\Mail\StatusLamaran($user, $this->status, $lamaran, $this->pesan));

            // throttle mailtrap
            sleep(10);

            EmailBlastLog::where('id', $this->logId)->update([
                'status_kirim' => 'berhasil',
                'updated_at' => now()
            ]);
        } catch (\Throwable $e) {

            EmailBlastLog::where('id', $this->logId)->update([
                'status_kirim' => 'gagal',
                'updated_at' => now()
            ]);

            throw $e;
        }
    }
}
