<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendProsesLamaranEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $status;
    public $lamaranId;
    public $logId;

    public function __construct($userId, $status, $lamaranId, $logId)
    {
        $this->user = $userId; // simpan ID saja
        $this->status = $status;
        $this->lamaranId = $lamaranId;
        $this->logId = $logId;
    }

    public function handle()
    {
        try {
            $user = \App\Models\User::findOrFail($this->user);
            $lamaran = \App\Models\Lamaran::with('lowongan')->findOrFail($this->lamaranId);

            Mail::to($user->email)->send(new \App\Mail\StatusLamaran($user, $this->status, $lamaran));

            \App\Models\EmailBlastLog::find($this->logId)->update([
                'status_kirim' => 'berhasil',
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            \App\Models\EmailBlastLog::find($this->logId)->update([
                'status_kirim' => 'gagal',
                'updated_at' => now()
            ]);
            report($e);
        }
    }
}
