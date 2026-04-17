<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailResetPassword extends Mailable
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
        //
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
            'token' => $this->detail['email_verifikasi_token'],
            'email' => $this->detail['email']
        ];
        return $this->from(config('mail.from.address'), config('mail.from.name'))->view('auth.reset-password-email', compact('data'))->with([
            'data' => $data
        ]);
    }
}
