<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailApprovedRecoveryAccount extends Mailable
{
    use Queueable, SerializesModels;

    public $detail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($detail)
    {
        $this->detail = $detail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->detail;

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Akun V-HIRE Berhasil Dipulihkan')
            ->view('auth.approved-recovery-account-email', compact('data'))
            ->with([
                'data' => $data,
            ]);
    }
}
