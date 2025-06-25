<?php

namespace App\Mail;

use App\Models\Lamaran;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StatusLamaran extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $status;
    public $lamaran;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $status, Lamaran $lamaran)
    {
        $this->user = $user;
        $this->status = $status;
        $this->lamaran = $lamaran;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('no-reply@vdni.top')
            ->subject('Informasi Rekrutmen PT VDNI')
            ->view('emails.status-lamaran')
            ->text('emails.status-lamaran_plain') // Plain text version
            ->with([
                'user' => $this->user,
                'status' => $this->status,
                'lamaran' => $this->lamaran
            ]);
    }
}
