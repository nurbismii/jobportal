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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Services\AssessmentLinkService;
use Tests\TestCase;

class AssessmentLinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
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

    public function test_admin_can_create_and_deactivate_a_lapangan_assessment_link()
    {
        $admin = User::create([
            'name' => 'Assessment Admin',
            'email' => 'admin-assessment-link@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);
        $biodata = Biodata::create(['user_id' => $admin->id]);
        $lamaran = Lamaran::create([
            'biodata_id' => $biodata->id,
            'user_id' => $admin->id,
            'status_proses' => 'Tes Lapangan',
        ]);

        $response = $this->actingAs($admin)->post(route('assessment-links.store'), [
            'assessment_type' => 'lapangan',
            'pin' => '123456',
            'selected_ids' => [$lamaran->id],
            'fields' => [[
                'id' => 'run_time',
                'label' => 'Waktu lari',
                'type' => 'number',
                'required' => '1',
            ]],
        ]);

        $response->assertRedirect(route('assessment-links.index'));

        $link = AssessmentLink::query()->sole();
        $publicUrl = route('assessment-links.public.show', $link->public_token);
        $response->assertSessionHas('assessment_link_url', $publicUrl);
        $this->assertTrue(Hash::check('123456', $link->pin_hash));
        $this->assertSame([$lamaran->id], $link->candidates()->pluck('lamaran_id')->map(function ($id) {
            return (int) $id;
        })->all());
        $this->get($publicUrl)->assertOk()->assertViewIs('public-assessment-links.pin');
        $this->post(route('assessment-links.public.unlock', $link->public_token), ['pin' => '123456'])
            ->assertRedirect($publicUrl);

        $this->actingAs($admin)
            ->post(route('assessment-links.deactivate', $link))
            ->assertRedirect(route('assessment-links.show', $link));

        $this->assertDatabaseHas('assessment_links', [
            'id' => $link->id,
            'is_active' => false,
            'deactivated_by' => $admin->id,
        ]);

    }

    public function test_pin_is_never_flashed_back_after_assessment_link_submission_errors()
    {
        $admin = User::create([
            'name' => 'Assessment Admin',
            'email' => 'assessment-link-errors@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);
        $biodata = Biodata::create(['user_id' => $admin->id]);
        $lamaran = Lamaran::create([
            'biodata_id' => $biodata->id,
            'user_id' => $admin->id,
            'status_proses' => 'Tes Lapangan',
        ]);

        $this->actingAs($admin)->from(route('assessment-links.create'))->post(route('assessment-links.store'), [
            'assessment_type' => 'lapangan',
            'pin' => '123',
            'selected_ids' => [$lamaran->id],
        ])->assertRedirect(route('assessment-links.create'))
            ->assertSessionHasErrors('pin')
            ->assertSessionMissing('_old_input.pin');

        $this->actingAs($admin)->from(route('assessment-links.create'))->post(route('assessment-links.store'), [
            'assessment_type' => 'lapangan',
            'pin' => '123456',
            'selected_ids' => [$lamaran->id],
            'fields' => [[
                'id' => 'surface',
                'label' => 'Permukaan',
                'type' => 'select',
            ]],
        ])->assertRedirect(route('assessment-links.create'))
            ->assertSessionMissing('_old_input.pin');
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
            'status_proses' => 'Tes Kesehatan',
        ]);

        $service = app(AssessmentLinkService::class);
        $link = $service->create([
            'assessment_type' => 'kesehatan',
            'pin' => '123456',
            'fields' => [['id' => 'blood_pressure', 'label' => 'Tekanan darah', 'type' => 'number', 'required' => true]],
        ], [$lamaran->id], $admin->id);

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

    public function test_create_rejects_non_array_schema_fields()
    {
        $service = app(AssessmentLinkService::class);

        $this->expectException(ValidationException::class);

        $service->create([
            'assessment_type' => 'lapangan',
            'pin' => '123456',
            'fields' => 'not-an-array',
        ], [], 1);
    }

    public function test_save_result_rejects_an_inactive_link()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:00:00', 'Asia/Makassar'));

        $admin = User::create([
            'name' => 'Assessment Admin',
            'email' => 'inactive-link-admin@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);
        $biodata = Biodata::create(['user_id' => $admin->id]);
        $lamaran = Lamaran::create([
            'biodata_id' => $biodata->id,
            'user_id' => $admin->id,
            'status_proses' => 'Tes Kesehatan',
        ]);
        $service = app(AssessmentLinkService::class);
        $link = $service->create([
            'assessment_type' => 'kesehatan',
            'pin' => '123456',
            'fields' => [],
        ], [$lamaran->id], $admin->id);
        $link->update(['is_active' => false]);

        $this->expectException(ModelNotFoundException::class);

        $service->saveResult($link->candidates->first(), ['health_status' => 'Sehat'], null, Request::create('/assessment', 'POST'));
    }

    public function test_public_assessment_requires_pin_before_results_can_be_saved()
    {
        [$link, $candidate] = $this->createPublicAssessmentLink();

        $this->post(route('assessment-links.public.results.store', [$link->public_token, $candidate]), [
            'values' => ['run_time' => '12.5'],
            'petugas_note' => 'Catatan petugas',
        ])->assertForbidden();
    }

    public function test_public_assessment_rejects_an_unlocked_missing_payload_before_validation()
    {
        [$link, $candidate] = $this->createPublicAssessmentLink();

        $this->post(route('assessment-links.public.results.store', [$link->public_token, $candidate]))
            ->assertForbidden();
    }

    public function test_public_assessment_unlocks_with_valid_pin_and_saves_result_without_changing_lamaran()
    {
        [$link, $candidate] = $this->createPublicAssessmentLink();
        $lamaranBefore = $candidate->lamaran->getAttributes();

        $this->post(route('assessment-links.public.unlock', $link->public_token), ['pin' => '123456'])
            ->assertRedirect(route('assessment-links.public.show', $link->public_token));

        $this->assertTrue(session()->has('assessment_link_access.'.$link->id));

        $this->post(route('assessment-links.public.results.store', [$link->public_token, $candidate]), [
            'values' => ['run_time' => '12.5'],
            'petugas_note' => 'Catatan petugas',
        ])->assertRedirect(route('assessment-links.public.show', $link->public_token));

        $candidate->refresh();
        $candidate->lamaran->refresh();
        $this->assertSame('12.5', $candidate->result_values['run_time']);
        $this->assertSame('Catatan petugas', $candidate->petugas_note);
        $this->assertSame($lamaranBefore, $candidate->lamaran->getAttributes());
    }

    public function test_public_assessment_does_not_unlock_with_invalid_pin()
    {
        [$link] = $this->createPublicAssessmentLink();

        $this->from(route('assessment-links.public.show', $link->public_token))
            ->post(route('assessment-links.public.unlock', $link->public_token), ['pin' => 'wrong-pin'])
            ->assertRedirect(route('assessment-links.public.show', $link->public_token))
            ->assertSessionHasErrors('pin');

        $this->assertFalse(session()->has('assessment_link_access.'.$link->id));
        $this->get(route('assessment-links.public.show', $link->public_token))
            ->assertOk()
            ->assertViewIs('public-assessment-links.pin');
    }

    public function test_public_assessment_returns_not_found_for_expired_or_deactivated_link()
    {
        [$expired] = $this->createPublicAssessmentLink(['expires_at' => now('Asia/Makassar')->subMinute()]);
        [$inactive] = $this->createPublicAssessmentLink(['is_active' => false]);

        foreach ([$expired, $inactive] as $link) {
            $this->get(route('assessment-links.public.show', $link->public_token))->assertNotFound();
            $this->post(route('assessment-links.public.unlock', $link->public_token), ['pin' => '123456'])->assertNotFound();
        }
    }

    public function test_public_assessment_result_save_returns_not_found_for_expired_or_deactivated_link_even_with_session_access()
    {
        [$expired, $expiredCandidate] = $this->createPublicAssessmentLink([
            'expires_at' => now('Asia/Makassar')->subMinute(),
        ]);
        [$inactive, $inactiveCandidate] = $this->createPublicAssessmentLink(['is_active' => false]);

        foreach ([[$expired, $expiredCandidate], [$inactive, $inactiveCandidate]] as [$link, $candidate]) {
            $this->withSession(['assessment_link_access.'.$link->id => true])
                ->post(route('assessment-links.public.results.store', [$link->public_token, $candidate]), [
                    'values' => ['run_time' => '12.5'],
                ])
                ->assertNotFound();
        }
    }

    public function test_public_assessment_rejects_candidate_from_another_link_and_invalid_select_option()
    {
        [$link, $candidate] = $this->createPublicAssessmentLink([
            'form_schema' => [[
                'id' => 'result', 'label' => 'Hasil', 'type' => 'select', 'required' => true, 'options' => ['Lulus', 'Tidak Lulus'],
            ]],
        ]);
        [, $foreignCandidate] = $this->createPublicAssessmentLink();
        $this->withSession(['assessment_link_access.'.$link->id => true]);

        $this->post(route('assessment-links.public.results.store', [$link->public_token, $foreignCandidate]), [
            'values' => ['result' => 'Lulus'],
        ])->assertNotFound();

        $this->post(route('assessment-links.public.results.store', [$link->public_token, $candidate]), [
            'values' => ['result' => 'Tidak Valid'],
        ])->assertSessionHasErrors('values.result');
    }

    public function test_unlocked_public_link_lists_candidates_by_name_or_ktp_with_pagination()
    {
        $link = $this->createLapanganLinkWithCandidates(30);

        $this->withSession(['assessment_link_access.'.$link->id => true])
            ->getJson(route('assessment-links.public.candidates', [$link->public_token, 'q' => 'KTP-001', 'status' => 'all']))
            ->assertOk()
            ->assertJsonPath('per_page', 25)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.no_ktp', 'KTP-001');
    }

    public function test_public_candidate_list_requires_pin_session()
    {
        $link = $this->createLapanganLinkWithCandidates(1);

        $this->getJson(route('assessment-links.public.candidates', $link->public_token))
            ->assertForbidden();
    }

    public function test_unlocked_public_link_autosaves_result_audits_and_preserves_lamaran()
    {
        [$link, $candidate] = $this->createPublicAssessmentLink();
        $lamaranBefore = $candidate->lamaran->getAttributes();

        $this->withSession(['assessment_link_access.'.$link->id => true])
            ->postJson(route('assessment-links.public.autosave', [$link->public_token, $candidate]), [
                'values' => ['run_time' => '12.5'],
                'petugas_note' => 'Catatan autosave',
            ])
            ->assertOk()
            ->assertJsonPath('result_values.run_time', '12.5')
            ->assertJsonPath('petugas_note', 'Catatan autosave')
            ->assertJsonStructure(['saved_at', 'result_values', 'petugas_note']);

        $candidate->refresh();
        $candidate->lamaran->refresh();
        $this->assertCount(1, $candidate->audits);
        $this->assertSame($lamaranBefore, $candidate->lamaran->getAttributes());
    }

    public function test_public_assessment_table_page_exposes_search_and_autosave_ui()
    {
        [$link] = $this->createPublicAssessmentLink();

        $this->withSession(['assessment_link_access.'.$link->id => true])
            ->get(route('assessment-links.public.show', $link->public_token))
            ->assertOk()
            ->assertSee('Cari nama atau nomor KTP')
            ->assertSee('Status simpan')
            ->assertSee('autosave');
    }

    public function test_create_page_only_lists_candidates_eligible_for_the_selected_assessment_type()
    {
        $admin = User::create(['name' => 'Assessment Admin', 'email' => 'create-list@example.test', 'password' => 'secret', 'role' => 'admin']);
        $lapanganUser = User::create(['name' => 'Kandidat Lapangan', 'email' => 'lapangan@example.test', 'password' => 'secret']);
        $kesehatanUser = User::create(['name' => 'Kandidat Kesehatan', 'email' => 'kesehatan@example.test', 'password' => 'secret']);
        $lapangan = Lamaran::create([
            'biodata_id' => Biodata::create(['user_id' => $lapanganUser->id, 'no_ktp' => 'KTP-LAP'])->id,
            'user_id' => $lapanganUser->id,
            'status_proses' => 'Tes Lapangan',
        ]);
        $kesehatan = Lamaran::create([
            'biodata_id' => Biodata::create(['user_id' => $kesehatanUser->id, 'no_ktp' => 'KTP-KES'])->id,
            'user_id' => $kesehatanUser->id,
            'status_proses' => 'Tes Kesehatan',
        ]);

        $this->actingAs($admin)->get(route('assessment-links.create'))
            ->assertOk()
            ->assertSee('Kandidat Lapangan')
            ->assertSee('Kandidat Kesehatan')
            ->assertSee('data-assessment-type="lapangan"', false)
            ->assertSee('data-assessment-type="kesehatan"', false)
            ->assertSee('value="'.$lapangan->id.'"', false)
            ->assertSee('value="'.$kesehatan->id.'"', false);
    }

    public function test_admin_cannot_create_link_with_candidate_from_another_assessment_stage()
    {
        $admin = User::create(['name' => 'Assessment Admin', 'email' => 'strict-create@example.test', 'password' => 'secret', 'role' => 'admin']);
        $user = User::create(['name' => 'Kandidat Kesehatan', 'email' => 'strict-candidate@example.test', 'password' => 'secret']);
        $lamaran = Lamaran::create([
            'biodata_id' => Biodata::create(['user_id' => $user->id])->id,
            'user_id' => $user->id,
            'status_proses' => 'Tes Kesehatan',
        ]);

        $this->actingAs($admin)->from(route('assessment-links.create'))->post(route('assessment-links.store'), [
            'assessment_type' => 'lapangan',
            'pin' => '123456',
            'selected_ids' => [$lamaran->id],
        ])->assertRedirect(route('assessment-links.create'))
            ->assertSessionHasErrors('selected_ids.0');

        $this->assertSame(0, AssessmentLink::query()->count());
    }

    public function test_admin_can_add_only_candidates_in_the_matching_assessment_stage_and_see_their_position()
    {
        $admin = User::create(['name' => 'HR Admin', 'email' => 'hr-add@example.test', 'password' => 'secret', 'role' => 'admin']);
        $lowongan = DB::table('lowongan')->insertGetId(['nama_lowongan' => 'Operator Alat Berat']);
        $eligibleUser = User::create(['name' => 'Kandidat Tes Lapangan', 'email' => 'eligible@example.test', 'password' => 'secret']);
        $eligibleBiodata = Biodata::create(['user_id' => $eligibleUser->id, 'no_ktp' => 'KTP-ELIGIBLE']);
        $eligibleLamaran = Lamaran::create([
            'biodata_id' => $eligibleBiodata->id,
            'user_id' => $eligibleUser->id,
            'loker_id' => $lowongan,
            'status_proses' => 'Tes Lapangan',
        ]);
        $otherLamaran = Lamaran::create([
            'biodata_id' => $eligibleBiodata->id,
            'user_id' => $eligibleUser->id,
            'status_proses' => 'Tes Kesehatan',
        ]);
        $link = AssessmentLink::create([
            'assessment_type' => 'lapangan',
            'public_token' => str_repeat('a', 64),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [],
            'created_by' => $admin->id,
            'expires_at' => now('Asia/Makassar')->addDay(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('assessment-links.candidates.store', $link), ['lamaran_ids' => [$eligibleLamaran->id]])
            ->assertRedirect(route('assessment-links.show', $link));

        $this->assertDatabaseHas('assessment_link_candidates', ['assessment_link_id' => $link->id, 'lamaran_id' => $eligibleLamaran->id]);
        $this->actingAs($admin)->get(route('assessment-links.show', $link))
            ->assertOk()
            ->assertSee('Operator Alat Berat')
            ->assertSee('KTP-ELIGIBLE');

        $this->actingAs($admin)
            ->post(route('assessment-links.candidates.store', $link), ['lamaran_ids' => [$otherLamaran->id]])
            ->assertSessionHasErrors('lamaran_ids.0');
    }

    public function test_admin_cannot_add_a_duplicate_assessment_candidate()
    {
        [$link, $candidate] = $this->createPublicAssessmentLink();
        $admin = $link->creator;

        $this->actingAs($admin)
            ->post(route('assessment-links.candidates.store', $link), ['lamaran_ids' => [$candidate->lamaran_id]])
            ->assertSessionHasErrors('lamaran_ids.0');

        $this->assertSame(1, AssessmentLinkCandidate::query()->where('assessment_link_id', $link->id)->count());
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
            $table->string('no_ktp')->nullable();
            $table->timestamps();
        });

        Schema::create('lamaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biodata_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('loker_id')->nullable();
            $table->string('status_proses')->nullable();
            $table->timestamps();
        });

        Schema::create('lowongan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lowongan')->nullable();
        });
    }

    /** @return array{0: AssessmentLink, 1: AssessmentLinkCandidate} */
    private function createPublicAssessmentLink(array $overrides = []): array
    {
        $suffix = (string) (AssessmentLink::query()->count() + 1);
        $user = User::create([
            'name' => 'Public Assessment '.$suffix,
            'email' => 'public-assessment-'.$suffix.'@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);
        $biodata = Biodata::create(['user_id' => $user->id]);
        $lamaran = Lamaran::create(['biodata_id' => $biodata->id, 'user_id' => $user->id]);
        $link = AssessmentLink::create(array_merge([
            'assessment_type' => 'lapangan',
            'public_token' => str_pad($suffix, 64, 'p'),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [['id' => 'run_time', 'label' => 'Waktu lari', 'type' => 'number', 'required' => true]],
            'created_by' => $user->id,
            'expires_at' => now('Asia/Makassar')->addDay(),
            'is_active' => true,
        ], $overrides));
        $candidate = AssessmentLinkCandidate::create([
            'assessment_link_id' => $link->id,
            'lamaran_id' => $lamaran->id,
        ]);

        return [$link, $candidate->load('lamaran')];
    }

    private function createLapanganLinkWithCandidates(int $count): AssessmentLink
    {
        $admin = User::create([
            'name' => 'Assessment Admin',
            'email' => 'assessment-list-admin-'.AssessmentLink::query()->count().'@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);
        $link = AssessmentLink::create([
            'assessment_type' => 'lapangan',
            'public_token' => str_pad((string) (AssessmentLink::query()->count() + 1), 64, 'l'),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [['id' => 'run_time', 'label' => 'Waktu lari', 'type' => 'number', 'required' => true]],
            'created_by' => $admin->id,
            'expires_at' => now('Asia/Makassar')->addDay(),
            'is_active' => true,
        ]);

        for ($index = 1; $index <= $count; $index++) {
            $user = User::create([
                'name' => 'Kandidat '.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'email' => 'assessment-list-'.$link->id.'-'.$index.'@example.test',
                'password' => 'secret',
            ]);
            $biodata = Biodata::create([
                'user_id' => $user->id,
                'no_ktp' => 'KTP-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            ]);
            $lamaran = Lamaran::create(['biodata_id' => $biodata->id, 'user_id' => $user->id]);
            AssessmentLinkCandidate::create(['assessment_link_id' => $link->id, 'lamaran_id' => $lamaran->id]);
        }

        return $link;
    }
}
