<?php

namespace App\Jobs;

use App\Mail\HrBlastEmail;
use App\Models\HrEmailDelivery;
use App\Models\User;
use App\Services\FallbackMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

class SendHrEmail implements ShouldQueue
{
    use Dispatchable, Queueable;

    // SMTP may accept a message before a timeout; automatic retries risk duplicates.
    public $tries = 1;
    public $timeout = 60;

    public function __construct(public int $deliveryId) {}

    public function handle(FallbackMailService $mailer): void
    {
        $delivery = HrEmailDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->status !== 'pending') {
            return;
        }
        $eligible = User::whereKey($delivery->user_id)->where('email', $delivery->recipient)
            ->where('role', 'user')->where('status_akun', 1)->whereNotNull('email_verified_at')->exists();
        if (! $eligible) {
            $delivery->update(['status' => 'skipped']);
            return;
        }
        try {
            $mailer->send($delivery->recipient, new HrBlastEmail($delivery->subject, $delivery->message, $delivery->attachments ?? []));
            $delivery->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        HrEmailDelivery::whereKey($this->deliveryId)->where('status', 'pending')->update(['status' => 'failed']);
    }
}
