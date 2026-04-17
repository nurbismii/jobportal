<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Swift_TransportException;
use Throwable;

class FallbackMailService
{
    public function send($to, Mailable $mailable): void
    {
        $primaryMailer = $this->resolvePrimaryMailer();

        try {
            $this->sendViaMailer($primaryMailer, $to, clone $mailable, false);
        } catch (Throwable $exception) {
            if (! $this->shouldRetryWithSecondary($primaryMailer, $exception)) {
                throw $exception;
            }

            $secondaryMailer = config('mail.secondary_mailer', 'smtp_second');

            Log::warning('Primary mailer failed. Retrying with secondary mailer.', [
                'primary_mailer' => $primaryMailer,
                'secondary_mailer' => $secondaryMailer,
                'recipient' => $this->stringifyRecipient($to),
                'error' => $exception->getMessage(),
            ]);

            try {
                $this->sendViaMailer($secondaryMailer, $to, clone $mailable, true);
            } catch (Throwable $secondaryException) {
                Log::error('Secondary mailer also failed.', [
                    'primary_mailer' => $primaryMailer,
                    'secondary_mailer' => $secondaryMailer,
                    'recipient' => $this->stringifyRecipient($to),
                    'primary_error' => $exception->getMessage(),
                    'secondary_error' => $secondaryException->getMessage(),
                ]);

                throw $secondaryException;
            }
        }
    }

    protected function sendViaMailer(string $mailer, $to, Mailable $mailable, bool $useSecondaryFrom): void
    {
        $originalFrom = config('mail.from');

        if ($useSecondaryFrom) {
            config([
                'mail.from' => config('mail.from_second', $originalFrom),
            ]);
        }

        try {
            Mail::purge($mailer);
            Mail::mailer($mailer)->to($to)->send($mailable);
        } finally {
            config([
                'mail.from' => $originalFrom,
            ]);
        }
    }

    protected function resolvePrimaryMailer(): string
    {
        $defaultMailer = config('mail.default', 'smtp');

        // For critical flows we try the real primary SMTP first so fallback
        // does not silently stop on the log transport.
        return $defaultMailer === 'failover' ? 'smtp' : $defaultMailer;
    }

    protected function shouldRetryWithSecondary(string $primaryMailer, Throwable $exception): bool
    {
        $secondaryMailer = config('mail.secondary_mailer', 'smtp_second');
        $secondaryHost = config("mail.mailers.{$secondaryMailer}.host");

        if ($primaryMailer === $secondaryMailer || blank($secondaryHost)) {
            return false;
        }

        return $exception instanceof Swift_TransportException
            || $exception instanceof InvalidArgumentException;
    }

    protected function stringifyRecipient($to): string
    {
        if (is_string($to)) {
            return $to;
        }

        return (string) json_encode($to);
    }
}
