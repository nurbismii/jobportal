<?php

namespace Tests\Feature;

use App\Jobs\SendHrEmail;
use App\Mail\HrBlastEmail;
use App\Models\HrEmailDelivery;
use App\Models\User;
use App\Services\FallbackMailService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HrEmailBlastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Test');
            $table->string('email')->unique();
            $table->string('password')->default('test');
            $table->string('role')->default('user');
            $table->integer('status_akun')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_02_000000_create_hr_email_deliveries_table.php'))->up();
        (require database_path('migrations/2026_10_02_010000_add_attachments_to_hr_email_deliveries.php'))->up();
        require_once database_path('migrations/2025_06_25_111144_create_jobs_table.php');
        (new \CreateJobsTable)->up();
    }

    public function test_attachments_are_private_shared_between_recipients_and_attached_to_email(): void
    {
        Storage::fake('hr_email_private');
        Queue::fake();
        $this->actingAs(User::create(['email' => 'attachment-hr@example.test', 'role' => 'admin', 'status_akun' => 1]));
        User::create(['email' => 'one@example.test', 'email_verified_at' => now()]);
        User::create(['email' => 'two@example.test', 'email_verified_at' => now()]);
        $this->post(route('email-blast-log.store'), [
            'recipients' => 'one@example.test,two@example.test', 'subject' => 'Dokumen', 'message' => '<p>Dokumen terlampir.</p>',
            'attachments' => [UploadedFile::fake()->createWithContent('undangan.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"), UploadedFile::fake()->image('foto.jpg')],
        ])->assertSessionHasNoErrors();
        $deliveries = HrEmailDelivery::all();
        $this->assertCount(2, $deliveries);
        $this->assertSame($deliveries[0]->attachments, $deliveries[1]->attachments);
        $this->assertCount(2, Storage::disk('hr_email_private')->allFiles());
        $mailer = $this->mock(FallbackMailService::class);
        $mailer->shouldReceive('send')->once()->with('one@example.test', \Mockery::on(function ($mail) use ($deliveries) {
            $mail->build();
            foreach ($deliveries[0]->attachments as $file) {
                $this->assertTrue($mail->hasAttachmentFromStorageDisk('hr_email_private', $file['path'], $file['name'], ['mime' => $file['mime']]));
            }
            return true;
        }));
        (new SendHrEmail($deliveries[0]->id))->handle($mailer);
        $this->assertSame('sent', $deliveries[0]->fresh()->status);
    }

    public function test_invalid_or_oversized_attachments_never_create_jobs_or_files(): void
    {
        Storage::fake('hr_email_private');
        Queue::fake();
        $this->actingAs(User::create(['email' => 'invalid-file-hr@example.test', 'role' => 'admin', 'status_akun' => 1]));
        User::create(['email' => 'recipient@example.test', 'email_verified_at' => now()]);
        $sets = [
            [UploadedFile::fake()->createWithContent('evil.php', '<?php echo 1;')],
            [UploadedFile::fake()->createWithContent('evil.pdf', '<?php echo 1;')->mimeType('text/plain')],
            [UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')],
            array_map(fn ($i) => UploadedFile::fake()->create("file{$i}.pdf", 1, 'application/pdf'), range(1, 6)),
            array_map(fn ($i) => UploadedFile::fake()->create("file{$i}.pdf", 4096, 'application/pdf'), range(1, 3)),
        ];
        foreach ($sets as $files) {
            $this->post(route('email-blast-log.store'), ['recipients' => 'recipient@example.test', 'subject' => 'Info', 'message' => 'Pesan', 'attachments' => $files])->assertSessionHasErrors();
        }
        $this->assertSame(0, HrEmailDelivery::count());
        $this->assertSame([], Storage::disk('hr_email_private')->allFiles());
        Queue::assertNothingPushed();
    }

    public function test_enqueue_failure_rolls_back_and_removes_uploaded_files(): void
    {
        Storage::fake('hr_email_private');
        $this->actingAs(User::create(['email' => 'rollback-hr@example.test', 'role' => 'admin', 'status_akun' => 1]));
        User::create(['email' => 'recipient@example.test', 'email_verified_at' => now()]);
        Schema::drop('jobs');
        $this->post(route('email-blast-log.store'), [
            'recipients' => 'recipient@example.test', 'subject' => 'Info', 'message' => 'Pesan',
            'attachments' => [UploadedFile::fake()->createWithContent('info.txt', 'Dokumen HR')],
        ])->assertSessionHasErrors('attachments');
        $this->assertSame(0, HrEmailDelivery::count());
        $this->assertSame([], Storage::disk('hr_email_private')->allFiles());
    }

    public function test_only_admin_can_access_and_verified_recipients_are_deduplicated(): void
    {
        Queue::fake();
        $url = route('email-blast-log.store');
        $payload = ['recipients' => "FIRST@example.test; first@example.test\nsecond@example.test", 'subject' => 'Info HR', 'message' => 'Pesan HR'];
        $this->post($url, $payload)->assertRedirect('/login');
        $first = User::create(['email' => 'first@example.test', 'email_verified_at' => now()]);
        User::create(['email' => 'second@example.test', 'email_verified_at' => now()]);
        $this->actingAs($first->fresh())->post($url, $payload)->assertRedirect('/');
        $admin = User::create(['email' => 'hr@example.test', 'role' => 'admin', 'status_akun' => 1]);
        $this->actingAs($admin)->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect(route('email-blast-log.create'));
        $this->assertSame(2, HrEmailDelivery::count());
        Queue::assertPushed(SendHrEmail::class, 2);
        Queue::assertPushed(SendHrEmail::class, fn ($job) => $job->connection === 'database');
        $this->get(route('email-blast-log.create'))->assertOk()->assertSee('Info HR');
    }

    public function test_invalid_unknown_unverified_inactive_and_excessive_recipients_are_rejected_atomically(): void
    {
        Queue::fake();
        $this->actingAs(User::create(['email' => 'hr@example.test', 'role' => 'admin', 'status_akun' => 1]));
        User::create(['email' => 'valid@example.test', 'email_verified_at' => now()]);
        User::create(['email' => 'unverified@example.test']);
        User::create(['email' => 'inactive@example.test', 'email_verified_at' => now(), 'status_akun' => 0]);
        foreach (['bad-address', 'unknown@example.test', 'unverified@example.test', 'inactive@example.test', 'hr@example.test'] as $email) {
            $this->post(route('email-blast-log.store'), ['recipients' => 'valid@example.test,'.$email, 'subject' => 'Info', 'message' => 'Pesan'])->assertSessionHasErrors();
        }
        $emails = implode(',', array_map(fn ($i) => "user{$i}@example.test", range(1, 201)));
        $this->post(route('email-blast-log.store'), ['recipients' => $emails, 'subject' => 'Info', 'message' => 'Pesan'])->assertSessionHasErrors();
        $this->assertSame(0, HrEmailDelivery::count());
        Queue::assertNothingPushed();
    }

    public function test_database_queue_enqueues_without_sending_in_the_request(): void
    {
        $admin = User::create(['email' => 'hr@example.test', 'role' => 'admin', 'status_akun' => 1]);
        User::create(['email' => 'valid@example.test', 'email_verified_at' => now()]);
        $this->mock(FallbackMailService::class)->shouldNotReceive('send');
        $this->actingAs($admin)->post(route('email-blast-log.store'), ['recipients' => 'valid@example.test', 'subject' => 'Info', 'message' => 'Pesan'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame('pending', HrEmailDelivery::first()->status);
    }

    public function test_worker_checks_verification_again_and_records_success_failure_and_no_duplicate(): void
    {
        $user = User::create(['email' => 'valid@example.test', 'email_verified_at' => now()]);
        $delivery = HrEmailDelivery::create(['user_id' => $user->id, 'sent_by' => 1, 'recipient' => $user->email, 'subject' => 'Info', 'message' => '<script>alert(1)</script>', 'status' => 'pending']);
        $this->assertStringNotContainsString('<script>', (new HrBlastEmail($delivery->subject, $delivery->message))->render());
        $mailer = $this->mock(FallbackMailService::class);
        $mailer->shouldReceive('send')->once()->with($user->email, \Mockery::type(HrBlastEmail::class));
        $job = new SendHrEmail($delivery->id);
        $job->handle($mailer);
        $job->handle($mailer);
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->sent_at);
        $delivery->refresh()->update(['status' => 'pending', 'sent_at' => null]);
        $user->update(['email_verified_at' => null]);
        $job->handle($mailer);
        $this->assertSame('skipped', $delivery->fresh()->status);
        $user->update(['email_verified_at' => now()]);
        $delivery->refresh()->update(['status' => 'pending']);
        $failingMailer = \Mockery::mock(FallbackMailService::class);
        $failingMailer->shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        try {
            $job->handle($failingMailer);
            $this->fail('Expected SMTP failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('failed', $delivery->fresh()->status);
        }
    }

    public function test_editor_formatting_is_preserved_and_unsafe_html_and_empty_messages_are_rejected(): void
    {
        $html = HrBlastEmail::sanitizeMessage('<p onclick="evil()">Halo <strong>pelamar</strong></p><ul><li>Tes</li></ul><script>alert(1)</script><img src="x" onerror="evil()"><a href="javascript:evil()">Tautan</a>');
        $this->assertSame('<p>Halo <strong>pelamar</strong></p><ul><li>Tes</li></ul>Tautan', $html);
        $this->assertSame("Baris 1<br />\nBaris 2", HrBlastEmail::sanitizeMessage("Baris 1\nBaris 2"));
        Queue::fake();
        $this->actingAs(User::create(['email' => 'hr-editor@example.test', 'role' => 'admin', 'status_akun' => 1]));
        User::create(['email' => 'editor-user@example.test', 'email_verified_at' => now()]);
        foreach (['<p><br></p>', '<p>&nbsp;</p>', '<script>alert(1)</script>'] as $message) {
            $this->post(route('email-blast-log.store'), ['recipients' => 'editor-user@example.test', 'subject' => 'Info', 'message' => $message])->assertSessionHasErrors('message');
        }
        Queue::assertNothingPushed();
        $this->post(route('email-blast-log.store'), ['recipients' => 'editor-user@example.test', 'subject' => 'Info', 'message' => '<p onclick="evil()"><strong>Info HR</strong></p>'])->assertSessionHasNoErrors();
        $this->assertSame('<p><strong>Info HR</strong></p>', HrEmailDelivery::first()->message);
        $this->assertStringContainsString('<strong>Info HR</strong>', (new HrBlastEmail('Info', HrEmailDelivery::first()->message))->render());
    }
}
