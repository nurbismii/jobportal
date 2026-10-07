<?php

namespace Tests\Feature;

use App\Models\Biodata;
use App\Models\User;
use App\Services\DocumentCheck\DocumentCheck;
use App\Services\FallbackMailService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    private string $publicRoot;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Storage::fake('candidate_private');
        $this->publicRoot = storage_path('framework/testing/security-public-' . \Illuminate\Support\Str::uuid());
        File::ensureDirectoryExists($this->publicRoot);
        $this->app->usePublicPath($this->publicRoot);
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'no_ktp', 'email', 'password', 'role', 'remember_token', 'email_verifikasi_token'] as $field) $t->string($field)->nullable();
            $t->integer('status_akun')->default(1);
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamp('verification_email_last_sent_at')->nullable();
            $t->timestamps();
        });
        Schema::create('biodata', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->string('no_ktp'); $t->string('cv')->nullable(); $t->timestamps();
        });
        Schema::create('lowongan', function (Blueprint $t) {
            $t->id(); $t->date('tanggal_mulai'); $t->date('tanggal_berakhir'); $t->timestamps();
        });
        Schema::create('lamaran', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('biodata_id'); $t->unsignedBigInteger('loker_id');
            $t->integer('status_lamaran'); $t->string('status_proses'); $t->timestamps();
        });
        Schema::create('riwayat_proses_lamaran', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('lamaran_id');
            foreach (['tanggal_proses', 'jam', 'status_proses', 'tempat', 'pesan'] as $field) $t->string($field)->nullable();
            $t->timestamps();
        });
        Schema::create('password_resets', function (Blueprint $t) {
            $t->string('email'); $t->string('token'); $t->timestamp('created_at');
        });
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicRoot);
        parent::tearDown();
    }

    private function account(int $n = 1): User
    {
        return User::create(['name' => 'Test', 'no_ktp' => '640101010190000' . $n, 'email' => "$n@example.test",
            'password' => Hash::make('secret123'), 'role' => 'user', 'status_akun' => 1, 'email_verified_at' => now()]);
    }

    public function test_document_urls_and_table_cells_handle_missing_files(): void
    {
        foreach ([null, '', '   '] as $file) {
            $this->assertSame('', candidate_document_url('6401010101900001', $file));
        }
        $url = candidate_document_url('6401010101900001', 'cv.pdf');
        $this->assertStringContainsString('/dokumen-pelamar/6401010101900001/cv.pdf', $url);
        $view = '@if($url = candidate_document_url($nik, $file))<a href="{{ $url }}">Lihat</a>@else—@endif';
        $this->assertSame('—', \Illuminate\Support\Facades\Blade::render($view, ['nik' => '6401010101900001', 'file' => null]));
        $this->assertStringContainsString('cv.pdf', \Illuminate\Support\Facades\Blade::render($view, ['nik' => '6401010101900001', 'file' => 'cv.pdf']));
    }

    private function biodata(User $user): Biodata
    {
        return Biodata::create(['user_id' => $user->id, 'no_ktp' => $user->no_ktp]);
    }

    public function test_applicant_cannot_read_another_application(): void
    {
        $a = $this->account(); $b = $this->biodata($this->account(2));
        $id = DB::table('lamaran')->insertGetId(['biodata_id' => $b->id, 'loker_id' => 1, 'status_lamaran' => 1, 'status_proses' => 'Awal']);
        $this->actingAs($a)->get('/lamaran/' . $id)->assertNotFound();
    }

    public function test_submission_uses_authenticated_biodata_and_rejects_duplicates(): void
    {
        $a = $this->account(); $own = $this->biodata($a); $other = $this->biodata($this->account(2));
        $id = DB::table('lowongan')->insertGetId(['tanggal_mulai' => now()->subDay()->toDateString(), 'tanggal_berakhir' => now()->addDay()->toDateString()]);
        $this->mock(DocumentCheck::class, fn ($mock) => $mock->shouldReceive('checkDocument')->andReturnNull());
        $this->actingAs($a)->post('/lowongan-kerja', ['loker_id' => $id, 'biodata_id' => $other->id])->assertRedirect();
        $this->assertDatabaseHas('lamaran', ['biodata_id' => $own->id, 'loker_id' => $id]);
        $this->post('/lowongan-kerja', ['loker_id' => $id, 'biodata_id' => $other->id])->assertRedirect();
        $this->assertSame(1, DB::table('lamaran')->count());
        $this->assertDatabaseMissing('lamaran', ['biodata_id' => $other->id]);
    }

    public function test_guest_and_unverified_accounts_cannot_submit(): void
    {
        $this->post('/lowongan-kerja', [])->assertRedirect('/login');
        $user = $this->account(); $user->email_verified_at = null; $user->save();
        $this->actingAs($user)->post('/lowongan-kerja', [])->assertRedirect();
        $this->assertSame(0, DB::table('lamaran')->count());
    }

    public function test_documents_require_ownership_and_legacy_files_remain_readable(): void
    {
        $a = $this->account(); $bio = $this->biodata($this->account(2));
        $bio->cv = 'legacy.pdf'; $bio->save();
        File::ensureDirectoryExists(public_path($bio->no_ktp . '/dokumen'));
        File::put(public_path($bio->no_ktp . '/dokumen/legacy.pdf'), "%PDF-1.4\nlegacy");
        $url = candidate_document_url($bio->no_ktp, $bio->cv);
        $this->actingAs($a)->get($url)->assertForbidden();
        $this->actingAs($bio->user)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(candidate_document_url($bio->no_ktp, 'unlisted.pdf'))->assertNotFound();
    }

    public function test_upload_names_are_unique_and_cleanup_preserves_new_file(): void
    {
        $user = $this->account(); $bio = $this->biodata($user); $this->actingAs($user);
        $upload = fn () => Request::create('/', 'POST', [], [], ['cv' => UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\ncontent")]);
        $first = interventionImg(['cv' => 'CV'], $bio, $upload());
        $bio->cv = $first['files']['cv']; $bio->save();
        $second = interventionImg(['cv' => 'CV'], $bio, $upload());
        $this->assertNotSame($first['files']['cv'], $second['files']['cv']);
        foreach ($second['oldFiles'] as $old) unlink(candidate_document_path($user->no_ktp, $old));
        $this->assertFileExists(candidate_document_path($user->no_ktp, $second['files']['cv'], false));
        $this->assertFileDoesNotExist(public_path($user->no_ktp . '/dokumen/' . $second['files']['cv']));
    }

    public function test_expired_recovery_can_be_reissued_without_overwriting_verification_token(): void
    {
        $user = $this->account(); $user->email_verifikasi_token = 'verification'; $user->save();
        DB::table('password_resets')->insert(['email' => $user->email, 'token' => 'expired', 'created_at' => now()->subHours(2)]);
        $sentToken = null;
        $this->mock(FallbackMailService::class, function ($mock) use (&$sentToken) {
            $mock->shouldReceive('send')->once()->andReturnUsing(function ($email, $mail) use (&$sentToken) { $sentToken = $mail->detail->email_verifikasi_token; });
        });
        $this->post('/lupa-akun', ['no_ktp' => $user->no_ktp, 'email' => $user->email])->assertRedirect(route('login'));
        $this->assertNotNull($sentToken);
        $this->assertSame(hash('sha256', $sentToken), DB::table('password_resets')->value('token'));
        $this->assertSame('verification', $user->fresh()->email_verifikasi_token);
        $this->get('/lupa-akun/token/' . $sentToken)->assertOk();
    }

    public function test_expired_verification_is_rejected_and_valid_token_consumed(): void
    {
        $user = $this->account();
        $user->forceFill(['status_akun' => 0, 'email_verified_at' => null, 'email_verifikasi_token' => 'token', 'verification_email_last_sent_at' => now()->subHours(2)])->save();
        $this->get('/konfirmasi-email-token/token')->assertRedirect();
        $this->assertNull($user->fresh()->email_verified_at);
        $user->forceFill(['verification_email_last_sent_at' => now()])->save();
        $this->get('/konfirmasi-email-token/token')->assertOk();
        $this->assertNull($user->fresh()->email_verifikasi_token);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_password_change_invalidates_established_session(): void
    {
        $user = $this->account();
        $this->actingAs($user)->get('/profil')->assertOk();
        $user->password = Hash::make('new-secret'); $user->save();
        $this->get('/profil')->assertRedirect('/login');
    }

    public function test_profile_password_change_requires_current_password_and_preserves_read_only_email(): void
    {
        $user = $this->account(); $user->remember_token = 'old-remember'; $user->save();
        $payload = ['nama' => $user->name, 'no_ktp' => $user->no_ktp, 'email' => 'different@example.test',
            'password' => 'new-secret', 'password_confirmation' => 'new-secret', 'current_password' => 'wrong'];
        $this->actingAs($user)->patchJson('/profil/' . $user->id, $payload)->assertUnprocessable();
        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
        $payload['current_password'] = 'secret123';
        $this->patch('/profil/' . $user->id, $payload)->assertRedirect();
        $this->assertTrue(Hash::check('new-secret', $user->fresh()->password));
        $this->assertNull($user->fresh()->remember_token);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->get('/profil')->assertOk();
    }

    public function test_recovery_consumes_token_rotates_remember_token_and_requires_new_email_verification(): void
    {
        $user = $this->account(); $user->remember_token = 'old-remember'; $user->save();
        DB::table('password_resets')->insert(['email' => $user->email, 'token' => hash('sha256', 'reset-token'), 'created_at' => now()]);
        $this->mock(FallbackMailService::class, fn ($mock) => $mock->shouldReceive('send')->once());
        $this->patch('/lupa-akun/reset-token', ['email' => 'new@example.test', 'password' => 'new-secret', 'password_confirmation' => 'new-secret'])->assertRedirect();
        $fresh = $user->fresh();
        $this->assertSame('new@example.test', $fresh->email);
        $this->assertTrue(Hash::check('new-secret', $fresh->password));
        $this->assertNull($fresh->remember_token);
        $this->assertNull($fresh->email_verified_at);
        $this->assertSame(0, (int) $fresh->status_akun);
        $this->assertSame(0, DB::table('password_resets')->count());
        $this->get('/lupa-akun/token/reset-token')->assertRedirect();
    }

    public function test_active_recovery_is_not_reissued_and_expired_token_cannot_reset_password(): void
    {
        $user = $this->account();
        DB::table('password_resets')->insert(['email' => $user->email, 'token' => hash('sha256', 'reset-token'), 'created_at' => now()]);
        $this->mock(FallbackMailService::class, fn ($mock) => $mock->shouldNotReceive('send'));
        $this->post('/lupa-akun', ['no_ktp' => $user->no_ktp, 'email' => $user->email])->assertRedirect();
        $this->assertSame(hash('sha256', 'reset-token'), DB::table('password_resets')->value('token'));
        DB::table('password_resets')->update(['created_at' => now()->subHours(2)]);
        $this->patch('/lupa-akun/reset-token', ['email' => 'attacker@example.test', 'password' => 'new-secret', 'password_confirmation' => 'new-secret'])->assertRedirect();
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
    }

    public function test_migration_never_deletes_source_when_destination_checksum_differs(): void
    {
        $nik = '6401010101900001'; $relative = $nik . '/dokumen/legacy.pdf';
        File::ensureDirectoryExists(dirname(public_path($relative)));
        File::put(public_path($relative), "%PDF-1.4\nsource");
        Storage::disk('candidate_private')->put($relative, "%PDF-1.4\ndifferent");
        $this->artisan('documents:migrate-private', ['--execute' => true, '--delete-public' => true])->assertFailed();
        $this->assertFileExists(public_path($relative));
        $this->assertSame("%PDF-1.4\ndifferent", Storage::disk('candidate_private')->get($relative));
    }

    public function test_migration_dry_run_copy_and_verified_delete(): void
    {
        $nik = '6401010101900001'; $source = public_path($nik . '/dokumen/legacy.pdf');
        File::ensureDirectoryExists(dirname($source)); File::put($source, "%PDF-1.4\nlegacy");
        $this->artisan('documents:migrate-private')->assertSuccessful();
        $this->assertFileDoesNotExist(candidate_document_path($nik, 'legacy.pdf', false));
        $this->artisan('documents:migrate-private', ['--execute' => true])->assertSuccessful();
        $this->assertFileExists($source);
        $this->assertSame(hash_file('sha256', $source), hash_file('sha256', candidate_document_path($nik, 'legacy.pdf', false)));
        $this->artisan('documents:migrate-private', ['--execute' => true, '--delete-public' => true])->assertSuccessful();
        $this->assertFileDoesNotExist($source);
        $this->assertFileExists(candidate_document_path($nik, 'legacy.pdf', false));
    }
}
