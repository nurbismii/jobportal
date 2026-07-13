<?php

namespace Tests\Feature;

use App\Models\AssessmentLink;
use App\Models\AssessmentLinkCandidate;
use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\Services\AssessmentLinkService;
use Tests\TestCase;

class AssessmentLinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'assessment_testing',
            'database.connections.assessment_testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('assessment_testing');
        DB::setDefaultConnection('assessment_testing');

        $this->createBaseSchema();

        $migrationPath = database_path('migrations/2026_07_13_000000_create_assessment_link_tables.php');
        $this->assertFileExists($migrationPath);
        require_once $migrationPath;
        (new \CreateAssessmentLinkTables())->up();
    }

    public function test_assessment_link_persists_schema_accessibility_and_candidate_lamaran_relation()
    {
        $now = Carbon::parse('2026-07-13 10:00:00');
        Carbon::setTestNow($now);

        $admin = User::create([
            'name' => 'Assessment Admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);
        $biodata = Biodata::create(['user_id' => $admin->id]);
        $lamaran = Lamaran::create([
            'biodata_id' => $biodata->id,
            'user_id' => $admin->id,
        ]);

        $link = AssessmentLink::create([
            'assessment_type' => 'lapangan',
            'public_token' => str_repeat('a', 64),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [['id' => 'run_time', 'type' => 'number']],
            'created_by' => $admin->id,
            'expires_at' => $now->copy()->endOfDay(),
            'is_active' => true,
        ]);
        $candidate = AssessmentLinkCandidate::create([
            'assessment_link_id' => $link->id,
            'lamaran_id' => $lamaran->id,
        ]);

        $link->refresh();

        $this->assertSame([['id' => 'run_time', 'type' => 'number']], $link->form_schema);
        $this->assertTrue(Hash::check('123456', $link->pin_hash));
        $this->assertTrue($link->isAccessibleAt($now));
        $this->assertTrue($link->candidates()->whereKey($candidate->id)->exists());
        $this->assertTrue($candidate->lamaran->is($lamaran));
    }

    public function test_health_link_keeps_standard_field_and_audits_each_result_save()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:00:00', 'Asia/Makassar'));

        $admin = User::create([
            'name' => 'Assessment Admin',
            'email' => 'assessment-admin@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);
        $biodata = Biodata::create(['user_id' => $admin->id]);
        $lamaran = Lamaran::create([
            'biodata_id' => $biodata->id,
            'user_id' => $admin->id,
        ]);

        $service = app(AssessmentLinkService::class);
        $link = $service->create([
            'assessment_type' => 'kesehatan',
            'pin' => '123456',
            'fields' => [['id' => 'blood_pressure', 'label' => 'Tekanan darah', 'type' => 'number', 'required' => true]],
        ], [$lamaran->id, $lamaran->id], $admin->id);

        $this->assertSame([
            'id' => 'health_status',
            'label' => 'Hasil tes kesehatan',
            'type' => 'select',
            'required' => true,
            'options' => ['Sehat', 'Tidak Sehat'],
        ], $link->form_schema[0]);
        $this->assertTrue($service->verifyPin($link, '123456'));
        $this->assertCount(1, $link->candidates);

        $candidate = $link->candidates->first();
        $request = Request::create('/assessment', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Assessment Test',
        ]);

        $service->saveResult($candidate, [
            'health_status' => 'Sehat',
            'blood_pressure' => 120,
        ], 'Layak bekerja', $request);
        $service->saveResult($candidate, [
            'health_status' => 'Tidak Sehat',
            'blood_pressure' => 150,
        ], 'Perlu pemeriksaan lanjutan', $request);

        $candidate->refresh();

        $this->assertSame('Tidak Sehat', $candidate->result_values['health_status']);
        $this->assertSame('Perlu pemeriksaan lanjutan', $candidate->petugas_note);
        $this->assertCount(2, $candidate->audits);
        $this->assertSame('Sehat', $candidate->audits->first()->new_values['health_status']);
        $this->assertSame('Tidak Sehat', $candidate->audits->last()->new_values['health_status']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createBaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('biodata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('lamaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biodata_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }
}
