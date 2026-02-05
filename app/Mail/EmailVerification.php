<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
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
            'id' => $this->detail['id'],
            'name' => $this->detail['name'],
            'no_ktp' => $this->detail['no_ktp'],
            'email_verifikasi_token' => $this->detail['email_verifikasi_token'],
        ];
        return $this->from('no-reply@vdnisite.com')->view('auth.verify-email', compact('data'))->with([
            'data' => $data 
        ]);
    }
}
