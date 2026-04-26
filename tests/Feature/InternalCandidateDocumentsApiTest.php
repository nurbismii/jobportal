<?php

namespace Tests\Feature;

use App\Models\Biodata;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InternalCandidateDocumentsApiTest extends TestCase
{
    protected $diskRoot;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-26 08:00:00'));

        $this->diskRoot = storage_path('framework/testing/candidate-documents');

        config([
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'filesystems.disks.candidate_documents_test' => [
                'driver' => 'local',
                'root' => $this->diskRoot,
            ],
            'recruitment.internal_api.token' => 'test-token',
            'recruitment.candidate_documents.disk' => 'candidate_documents_test',
            'recruitment.candidate_documents.base_path' => '{no_ktp}/dokumen',
            'recruitment.candidate_documents.temporary_url_minutes' => 10,
        ]);

        DB::purge('testing');
        DB::setDefaultConnection('testing');

        File::deleteDirectory($this->diskRoot);
        File::ensureDirectoryExists($this->diskRoot);

        $this->createDatabaseSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        if ($this->diskRoot) {
            File::deleteDirectory($this->diskRoot);
        }

        parent::tearDown();
    }

    public function test_it_requires_the_internal_bearer_token()
    {
        $this->postJson('/api/internal/candidate-documents', [
            'no_ktp' => '1234567890123456',
        ])->assertUnauthorized();
    }

    public function test_it_returns_not_found_payload_when_candidate_does_not_exist()
    {
        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/internal/candidate-documents', [
                'no_ktp' => '1234567890123456',
            ]);

        $response->assertOk()
            ->assertExactJson([
                'found' => false,
                'documents' => [],
            ]);
    }

    public function test_it_returns_available_documents_with_signed_temporary_urls()
    {
        $this->createCandidate([
            'cv' => 'cv.pdf',
            'ktp' => '../ktp.pdf',
            'ijazah' => 'missing.pdf',
        ]);

        Storage::disk('candidate_documents_test')->put('1234567890123456/dokumen/cv.pdf', "%PDF-1.4\nTest");

        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/internal/candidate-documents', [
                'no_ktp' => '1234567890123456',
            ]);

        $response->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('candidate.no_ktp', '1234567890123456')
            ->assertJsonPath('candidate.name', 'Budi Santoso');

        $documents = $response->json('documents');

        $this->assertCount(1, $documents);
        $this->assertSame('cv', $documents[0]['type']);
        $this->assertSame('CV', $documents[0]['label']);
        $this->assertSame(now()->addMinutes(10)->toIso8601String(), $documents[0]['expires_at']);
        $this->assertStringContainsString('/api/internal/candidate-documents/1234567890123456/cv/preview', $documents[0]['preview_url']);
        $this->assertStringContainsString('/api/internal/candidate-documents/1234567890123456/cv/download', $documents[0]['download_url']);
        $this->assertStringNotContainsString(base_path(), $documents[0]['preview_url']);
        $this->assertStringNotContainsString($this->diskRoot, $documents[0]['download_url']);
    }

    public function test_signed_preview_is_inline_and_signed_download_is_attachment()
    {
        $this->createCandidate([
            'cv' => 'cv.pdf',
        ]);

        Storage::disk('candidate_documents_test')->put('1234567890123456/dokumen/cv.pdf', "%PDF-1.4\nTest");

        $documents = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/internal/candidate-documents', [
                'no_ktp' => '1234567890123456',
            ])
            ->json('documents');

        $previewResponse = $this->get($documents[0]['preview_url']);
        $previewResponse->assertOk();
        $this->assertStringContainsString('inline', $previewResponse->headers->get('content-disposition'));

        $downloadResponse = $this->get($documents[0]['download_url']);
        $downloadResponse->assertOk();
        $this->assertStringContainsString('attachment', $downloadResponse->headers->get('content-disposition'));
        $this->assertStringContainsString('1234567890123456 Budi Santoso - CV.pdf', $downloadResponse->headers->get('content-disposition'));
    }

    private function createDatabaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('no_ktp', 32)->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('biodata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('no_ktp', 32)->nullable();
            $table->string('cv')->nullable();
            $table->string('pas_foto')->nullable();
            $table->string('surat_lamaran')->nullable();
            $table->string('ijazah')->nullable();
            $table->string('ktp')->nullable();
            $table->string('sim_b_2')->nullable();
            $table->string('sio')->nullable();
            $table->string('skck')->nullable();
            $table->string('sertifikat_vaksin')->nullable();
            $table->string('kartu_keluarga')->nullable();
            $table->string('npwp')->nullable();
            $table->string('ak1')->nullable();
            $table->string('sertifikat_pendukung')->nullable();
            $table->timestamps();
        });
    }

    private function createCandidate(array $documents = []): Biodata
    {
        $user = User::create([
            'no_ktp' => '1234567890123456',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => 'secret',
        ]);

        return Biodata::create(array_merge([
            'user_id' => $user->id,
            'no_ktp' => '1234567890123456',
        ], $documents));
    }
}
