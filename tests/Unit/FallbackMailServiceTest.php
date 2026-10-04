<?php

namespace Tests\Unit;

use App\Mail\HrBlastEmail;
use App\Services\FallbackMailService;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class FallbackMailServiceTest extends TestCase
{
    public function test_symfony_transport_failure_uses_secondary_smtp(): void
    {
        config(['mail.default' => 'failover', 'mail.secondary_mailer' => 'smtp_second', 'mail.mailers.smtp_second.host' => 'secondary.example.test']);
        $service = \Mockery::mock(FallbackMailService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('sendViaMailer')->once()->ordered()
            ->with('smtp', 'recipient@example.test', \Mockery::type(HrBlastEmail::class), false)
            ->andThrow(new TransportException('Connection timed out'));
        $service->shouldReceive('sendViaMailer')->once()->ordered()
            ->with('smtp_second', 'recipient@example.test', \Mockery::type(HrBlastEmail::class), true);
        $service->send('recipient@example.test', new HrBlastEmail('Info', 'Pesan'));
        $this->assertTrue(true);
    }

    public function test_render_or_attachment_failure_does_not_trigger_smtp_fallback(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp_second.host' => 'secondary.example.test']);
        $service = \Mockery::mock(FallbackMailService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('sendViaMailer')->once()->with('smtp', \Mockery::any(), \Mockery::any(), false)
            ->andThrow(new \RuntimeException('Lampiran email tidak ditemukan.'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lampiran email tidak ditemukan.');
        $service->send('recipient@example.test', new HrBlastEmail('Info', 'Pesan'));
    }

    public function test_secondary_failure_is_propagated_instead_of_reported_as_success(): void
    {
        config(['mail.default' => 'smtp', 'mail.secondary_mailer' => 'smtp_second', 'mail.mailers.smtp_second.host' => 'secondary.example.test']);
        $service = \Mockery::mock(FallbackMailService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('sendViaMailer')->once()->with('smtp', \Mockery::any(), \Mockery::any(), false)
            ->andThrow(new TransportException('Primary timeout'));
        $service->shouldReceive('sendViaMailer')->once()->with('smtp_second', \Mockery::any(), \Mockery::any(), true)
            ->andThrow(new TransportException('Secondary authentication failed'));
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Secondary authentication failed');
        $service->send('recipient@example.test', new HrBlastEmail('Info', 'Pesan'));
    }
}
