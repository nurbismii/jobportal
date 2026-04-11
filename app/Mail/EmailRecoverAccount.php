<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailRecoverAccount extends Mailable
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
        $data = [
            'name' => $this->detail['name'],
            'email' => $this->detail['email'],
            'no_ktp' => $this->detail['no_ktp'],
            'token' => $this->detail['email_verifikasi_token'],
        ];

        return $this->from('no-reply@vdnisite.com')
            ->subject('Pemulihan Akun V-HIRE')
            ->view('auth.recover-account-email', compact('data'))
            ->with([
                'data' => $data,
            ]);
    }
}
