<?php

namespace Tests\Feature;

use App\Models\AssessmentLink;
use App\Models\AssessmentLinkCandidate;
use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\User;
use App\Http\Controllers\Admin\LowonganController;
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
    public function test_pin_update_supports_legacy_links_and_invalidates_previous_public_access(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'update-pin@example.test', 'password' => 'secret', 'role' => 'admin']);
        $link = app(AssessmentLinkService::class)->create(['assessment_type' => 'mcu', 'pin' => 'old-pin'], [], $admin->id);
        $link->update(['pin_encrypted' => null]);
        $token = $link->public_token;
        $this->post(route('assessment-links.public.unlock', $token), ['pin' => 'old-pin'])->assertRedirect();
        $this->get(route('assessment-links.public.show', $token))->assertRedirect();
        $this->actingAs($admin)->patch(route('assessment-links.pin.update', $link), ['pin' => 'new-pin', 'pin_confirmation' => 'new-pin'])->assertSessionHasNoErrors();
        $link->refresh();
        $this->assertSame($token, $link->public_token);
        $this->assertSame('new-pin', $link->pin_encrypted);
        $this->assertTrue(Hash::check('new-pin', $link->pin_hash));
        $this->assertFalse(Hash::check('old-pin', $link->pin_hash));
        $this->get(route('assessment-links.pin', $link))->assertJson(['pin' => 'new-pin']);
        $this->get(route('assessment-links.public.show', $token))->assertOk()->assertSee('Verifikasi PIN');
        $this->get(route('assessment-documents.index', $token))->assertForbidden();
        $this->post(route('assessment-links.public.unlock', $token), ['pin' => 'new-pin'])->assertRedirect();
        $this->get(route('assessment-links.public.show', $token))->assertRedirect();
    }

    public function test_invalid_pin_update_never_flashes_secrets_and_requires_admin(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'invalid-pin@example.test', 'password' => 'secret', 'role' => 'admin']);
        $link = app(AssessmentLinkService::class)->create(['assessment_type' => 'mcu', 'pin' => 'old-pin'], [], $admin->id);
        $url = route('assessment-links.pin.update', $link);
        $this->patch($url, ['pin' => 'new-pin', 'pin_confirmation' => 'new-pin'])->assertRedirect('/login');
        $this->actingAs($admin)->patch($url, ['pin' => 'new-pin', 'pin_confirmation' => 'mismatch'])->assertSessionHasErrors('pin');
        $this->assertNull(session()->getOldInput('pin'));
        $this->assertNull(session()->getOldInput('pin_confirmation'));
        $this->assertTrue(Hash::check('old-pin', $link->fresh()->pin_hash));
        $admin->role = 'user';
        $this->actingAs($admin)->patch($url, ['pin' => 'new-pin', 'pin_confirmation' => 'new-pin'])->assertRedirect('/');
    }

    public function test_pin_is_encrypted_hidden_from_serialization_and_only_revealed_to_admin(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'pin-admin@example.test', 'password' => 'secret', 'role' => 'admin']);
        $link = app(AssessmentLinkService::class)->create(['assessment_type' => 'mcu', 'pin' => 'ClinicTest827'], [], $admin->id);
        $this->assertNotSame('ClinicTest827', DB::table('assessment_links')->where('id', $link->id)->value('pin_encrypted'));
        $this->assertArrayNotHasKey('pin_encrypted', $link->toArray());
        $this->assertArrayNotHasKey('pin_hash', $link->toArray());
        $this->get(route('assessment-links.pin', $link))->assertRedirect('/login');
        $this->actingAs($admin)->get(route('assessment-links.show', $link))->assertOk()->assertDontSee('ClinicTest827');
        $response = $this->get(route('assessment-links.pin', $link));
        $response->assertOk()->assertJson(['pin' => 'ClinicTest827']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $admin->role = 'user';
        $this->actingAs($admin)->get(route('assessment-links.pin', $link))->assertRedirect('/');
        $admin->role = 'admin';
        $link->update(['pin_encrypted' => null]);
        $this->actingAs($admin)->get(route('assessment-links.pin', $link))->assertNotFound();
        $this->assertTrue(app(AssessmentLinkService::class)->verifyPin($link, 'ClinicTest827'));
    }

    public function test_mcu_link_is_created_without_candidates_and_opens_document_upload(): void
    {
        $admin = User::create(['name' => 'MCU Admin', 'email' => 'mcu-admin@example.test', 'password' => 'secret', 'role' => 'admin']);
        $this->actingAs($admin)->post(route('assessment-links.store'), [
            'assessment_type' => 'mcu', 'pin' => '123456',
        ])->assertSessionHasNoErrors()->assertRedirect(route('assessment-links.index'));
        $link = AssessmentLink::latest('id')->firstOrFail();
        $this->assertSame('Hasil MCU', $link->type_label);
        $this->assertSame([], $link->form_schema);
        $this->assertSame(0, $link->candidates()->count());
        $this->get(route('assessment-links.show', $link))->assertOk()->assertSee('Hasil MCU')
            ->assertDontSee('Tambah kandidat')->assertDontSee('Hasil kandidat');
        auth()->logout();
        $this->post(route('assessment-links.public.unlock', $link->public_token), ['pin' => '123456'])->assertRedirect();
        $this->get(route('assessment-links.public.show', $link->public_token))
            ->assertRedirect(route('assessment-documents.index', $link->public_token));
    }

    public function test_mcu_service_discards_candidate_selection_and_rejects_adding_candidates(): void
    {
        $service = app(AssessmentLinkService::class);
        $link = $service->create(['assessment_type' => 'mcu', 'pin' => '123456'], [99999], 1);
        $this->assertSame(0, $link->candidates()->count());
        $this->expectException(ValidationException::class);
        $service->addCandidates($link, [99999]);
    }

    public function test_mcu_expiry_can_be_extended_without_changing_url_or_reactivating_revoked_link(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'expiry@example.test', 'password' => 'secret', 'role' => 'admin']);
        $this->actingAs($admin)->post(route('assessment-links.store'), [
            'assessment_type' => 'mcu', 'pin' => '123456', 'expires_on' => now()->addMonth()->toDateString(),
        ])->assertSessionHasNoErrors();
        $link = AssessmentLink::latest('id')->firstOrFail();
        $token = $link->public_token;
        $pin = $link->pin_hash;
        $this->assertSame(now()->addMonth()->toDateString(), $link->expires_at->toDateString());
        $this->patch(route('assessment-links.expiry', $link), ['expires_on' => now()->addMonths(2)->toDateString()])->assertSessionHasNoErrors();
        $link->refresh();
        $this->assertSame($token, $link->public_token);
        $this->assertSame($pin, $link->pin_hash);
        $this->assertSame(now()->addMonths(2)->toDateString(), $link->expires_at->toDateString());
        $link->update(['is_active' => false]);
        $this->patch(route('assessment-links.expiry', $link), ['expires_on' => now()->addMonths(3)->toDateString()])->assertForbidden();
        $admin->role = 'user';
        $this->actingAs($admin)->patch(route('assessment-links.expiry', $link), ['expires_on' => now()->addMonths(3)->toDateString()])->assertRedirect('/');
    }

    public function test_detail_loads_confirmation_library_without_a_flash_alert(): void
    {
        config(['sweetalert.alwaysLoadJS' => false, 'sweetalert.neverLoadJS' => false]);
        $admin = User::create(['name' => 'Admin', 'email' => 'confirm@example.test', 'password' => 'secret', 'role' => 'admin']);
        $link = app(AssessmentLinkService::class)->create(['assessment_type' => 'mcu', 'pin' => '123456'], [], $admin->id);
        session()->forget(['alert.config', 'alert.delete']);

        $response = $this->actingAs($admin)->get(route('assessment-links.show', $link));
        $response->assertOk()->assertSeeInOrder(['vendor/sweetalert/sweetalert.all.js', 'Swal.fire('], false);
        $this->assertSame(1, substr_count($response->getContent(), 'vendor/sweetalert/sweetalert.all.js'));
        $this->assertFileExists(public_path('vendor/sweetalert/sweetalert.all.js'));
    }

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

        $profileMigrationPath = database_path('migrations/2026_08_09_000000_create_biodata_minat_bakat_and_prestasi_tables.php');
        $this->assertFileExists($profileMigrationPath);
        require_once $profileMigrationPath;
        (new \CreateBiodataMinatBakatAndPrestasiTables())->up();

        $workExperienceMigrationPath = database_path('migrations/2026_08_09_010000_create_biodata_pengalaman_kerja_table.php');
        $this->assertFileExists($workExperienceMigrationPath);
        require_once $workExperienceMigrationPath;
        (new \CreateBiodataPengalamanKerjaTable())->up();

        $migrationPath = database_path('migrations/2026_07_13_000000_create_assessment_link_tables.php');
        $this->assertFileExists($migrationPath);
        require_once $migrationPath;
        (new \CreateAssessmentLinkTables())->up();
        (require database_path('migrations/2026_09_14_030000_add_pin_version_to_assessment_links.php'))->up();
        (require database_path('migrations/2026_09_14_020000_add_encrypted_pin_to_assessment_links.php'))->up();
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
            'eligibility_field_id' => 'run_result',
            'fields' => [[
                'id' => 'run_result',
                'label' => 'Hasil tes lari',
                'type' => 'select',
                'required' => '1',
                'options' => ['Lulus', 'Tidak Lulus'],
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

    public function test_create_page_exposes_lowongan_filter_pagination_and_visible_page_selection_controls()
    {
        $admin = User::create(['name' => 'Assessment Admin', 'email' => 'create-paging@example.test', 'password' => 'secret', 'role' => 'admin']);
        $lowonganId = DB::table('lowongan')->insertGetId([
            'nama_lowongan' => 'Operator Produksi',
            'created_at' => '2026-07-13 08:00:00',
            'updated_at' => '2026-07-13 08:00:00',
        ]);

        for ($index = 1; $index <= 26; $index++) {
            $user = User::create(['name' => 'Kandidat '.$index, 'email' => 'paging-'.$index.'@example.test', 'password' => 'secret']);
            $biodata = Biodata::create(['user_id' => $user->id, 'no_ktp' => 'KTP-PAGING-'.$index]);
            Lamaran::create([
                'biodata_id' => $biodata->id,
                'user_id' => $user->id,
                'loker_id' => $lowonganId,
                'status_proses' => 'Tes Lapangan',
            ]);
        }

        $this->actingAs($admin)->get(route('assessment-links.create'))
            ->assertOk()
            ->assertSee('Filter lowongan')
            ->assertSee('<option value="'.$lowonganId.'">Operator Produksi - 13 Juli 2026</option>', false)
            ->assertSee('value="25"', false)
            ->assertSee('value="50"', false)
            ->assertSee('value="100"', false)
            ->assertSee('value="all"', false)
            ->assertSee('Pilih semua pada halaman ini')
            ->assertSee('data-lowongan-id="'.$lowonganId.'"', false);
    }

    public function test_assessment_link_index_displays_each_unique_lowongan_with_its_creation_date_and_candidate_count()
    {
        $admin = User::create(['name' => 'Assessment Admin', 'email' => 'index-positions@example.test', 'password' => 'secret', 'role' => 'admin']);
        $operatorId = DB::table('lowongan')->insertGetId([
            'nama_lowongan' => 'Operator Produksi',
            'created_at' => '2026-07-13 08:00:00',
            'updated_at' => '2026-07-13 08:00:00',
        ]);
        $driverId = DB::table('lowongan')->insertGetId([
            'nama_lowongan' => 'Driver DT',
            'created_at' => '2026-07-14 08:00:00',
            'updated_at' => '2026-07-14 08:00:00',
        ]);
        $link = AssessmentLink::create([
            'assessment_type' => 'lapangan',
            'public_token' => str_repeat('s', 64),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [],
            'created_by' => $admin->id,
            'expires_at' => now('Asia/Makassar')->addDay(),
            'is_active' => true,
        ]);

        foreach ([$operatorId, $operatorId, $driverId] as $index => $lowonganId) {
            $user = User::create(['name' => 'Index Kandidat '.$index, 'email' => 'index-position-'.$index.'@example.test', 'password' => 'secret']);
            $lamaran = Lamaran::create([
                'biodata_id' => Biodata::create(['user_id' => $user->id])->id,
                'user_id' => $user->id,
                'loker_id' => $lowonganId,
                'status_proses' => 'Tes Lapangan',
            ]);
            AssessmentLinkCandidate::create(['assessment_link_id' => $link->id, 'lamaran_id' => $lamaran->id]);
        }

        $this->actingAs($admin)->get(route('assessment-links.index'))
            ->assertOk()
            ->assertSee('Lowongan / Posisi')
            ->assertSee('Operator Produksi - 13 Juli 2026 (2)')
            ->assertSee('Driver DT - 14 Juli 2026 (1)');
    }

    public function test_assessment_link_detail_displays_the_applied_position_with_its_creation_date()
    {
        $admin = User::create(['name' => 'Assessment Detail Admin', 'email' => 'detail-position@example.test', 'password' => 'secret', 'role' => 'admin']);
        $lowonganId = DB::table('lowongan')->insertGetId([
            'nama_lowongan' => 'Operator Produksi',
            'created_at' => '2026-07-13 08:00:00',
            'updated_at' => '2026-07-13 08:00:00',
        ]);
        $user = User::create(['name' => 'Kandidat Detail', 'email' => 'candidate-detail-position@example.test', 'password' => 'secret']);
        $lamaran = Lamaran::create([
            'biodata_id' => Biodata::create(['user_id' => $user->id, 'no_ktp' => 'KTP-DETAIL'])->id,
            'user_id' => $user->id,
            'loker_id' => $lowonganId,
            'status_proses' => 'Tes Lapangan',
        ]);
        $link = AssessmentLink::create([
            'assessment_type' => 'lapangan',
            'public_token' => str_repeat('d', 64),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [],
            'created_by' => $admin->id,
            'expires_at' => now('Asia/Makassar')->addDay(),
            'is_active' => true,
        ]);
        AssessmentLinkCandidate::create(['assessment_link_id' => $link->id, 'lamaran_id' => $lamaran->id]);

        $this->actingAs($admin)->get(route('assessment-links.show', $link))
            ->assertOk()
            ->assertSee('Posisi dilamar')
            ->assertSee('Operator Produksi - 13 Juli 2026');
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

    public function test_lapangan_link_stores_only_a_valid_lulus_tidak_lulus_decision_field()
    {
        $admin = User::create(['name' => 'HR Eligibility', 'email' => 'eligibility-field@example.test', 'password' => 'secret', 'role' => 'admin']);
        $lamaran = $this->createLamaranForAssessment('Tes Lapangan');

        $this->actingAs($admin)->post(route('assessment-links.store'), [
            'assessment_type' => 'lapangan',
            'pin' => '123456',
            'selected_ids' => [$lamaran->id],
            'eligibility_field_id' => 'hasil_mengemudi',
            'fields' => [[
                'id' => 'hasil_mengemudi',
                'label' => 'Hasil mengemudi',
                'type' => 'select',
                'options' => ['Lulus', 'Tidak Lulus'],
            ]],
        ])->assertRedirect(route('assessment-links.index'));

        $this->assertSame('hasil_mengemudi', AssessmentLink::query()->sole()->form_schema[0]['eligibility_decision_field']);

        $this->actingAs($admin)->from(route('assessment-links.create'))->post(route('assessment-links.store'), [
            'assessment_type' => 'lapangan',
            'pin' => '123456',
            'selected_ids' => [$lamaran->id],
            'eligibility_field_id' => 'hasil_mengemudi',
            'fields' => [[
                'id' => 'hasil_mengemudi',
                'label' => 'Hasil mengemudi',
                'type' => 'select',
                'options' => ['Lulus', 'Cadangan'],
            ]],
        ])->assertSessionHasErrors('eligibility_field_id');
    }

    public function test_detail_link_shows_and_filters_eligibility_without_changing_lamaran_status()
    {
        $admin = User::create(['name' => 'HR Eligibility Detail', 'email' => 'eligibility-detail@example.test', 'password' => 'secret', 'role' => 'admin']);
        $link = AssessmentLink::create([
            'assessment_type' => 'kesehatan',
            'public_token' => str_repeat('e', 64),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [['id' => 'health_status', 'label' => 'Hasil tes kesehatan', 'type' => 'select', 'required' => true, 'options' => ['Sehat', 'Tidak Sehat']]],
            'created_by' => $admin->id,
            'expires_at' => now('Asia/Makassar')->addDay(),
            'is_active' => true,
        ]);
        $healthy = $this->createLamaranForAssessment('Tes Kesehatan');
        $unhealthy = $this->createLamaranForAssessment('Tes Kesehatan');
        $waiting = $this->createLamaranForAssessment('Tes Kesehatan');
        AssessmentLinkCandidate::create(['assessment_link_id' => $link->id, 'lamaran_id' => $healthy->id, 'result_values' => ['health_status' => 'Sehat']]);
        AssessmentLinkCandidate::create(['assessment_link_id' => $link->id, 'lamaran_id' => $unhealthy->id, 'result_values' => ['health_status' => 'Tidak Sehat']]);
        AssessmentLinkCandidate::create(['assessment_link_id' => $link->id, 'lamaran_id' => $waiting->id]);

        $this->actingAs($admin)->get(route('assessment-links.show', $link))
            ->assertOk()
            ->assertSee('Layak lanjut')
            ->assertSee('Tidak layak')
            ->assertSee('Menunggu hasil');
        $this->actingAs($admin)->get(route('assessment-links.show', [$link, 'eligibility' => 'eligible']))
            ->assertOk()
            ->assertSee(optional(optional($healthy->biodata)->user)->name)
            ->assertDontSee('Tidak Sehat');
        $this->assertSame('Tes Kesehatan', $healthy->fresh()->status_proses);
    }

    public function test_lapangan_eligibility_uses_the_configured_lulus_tidak_lulus_field()
    {
        $admin = User::create(['name' => 'HR Lapangan Eligibility', 'email' => 'lapangan-eligibility@example.test', 'password' => 'secret', 'role' => 'admin']);
        $link = AssessmentLink::create([
            'assessment_type' => 'lapangan',
            'public_token' => str_repeat('l', 64),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [['id' => 'hasil_mengemudi', 'label' => 'Hasil mengemudi', 'type' => 'select', 'required' => true, 'options' => ['Lulus', 'Tidak Lulus'], 'eligibility_decision_field' => 'hasil_mengemudi']],
            'created_by' => $admin->id,
            'expires_at' => now('Asia/Makassar')->addDay(),
            'is_active' => true,
        ]);
        $candidate = AssessmentLinkCandidate::create([
            'assessment_link_id' => $link->id,
            'lamaran_id' => $this->createLamaranForAssessment('Tes Lapangan')->id,
            'result_values' => ['hasil_mengemudi' => 'Lulus'],
        ]);
        $candidate->setRelation('assessmentLink', $link);

        $this->assertSame('eligible', app(AssessmentLinkService::class)->eligibilityStatus($candidate));
        $candidate->result_values = ['hasil_mengemudi' => 'Tidak Lulus'];
        $this->assertSame('ineligible', app(AssessmentLinkService::class)->eligibilityStatus($candidate));
    }

    public function test_lowongan_pendaftar_uses_the_latest_assessment_result_and_filters_by_eligibility()
    {
        $admin = User::create(['name' => 'HR Lowongan', 'email' => 'lowongan-eligibility@example.test', 'password' => 'secret', 'role' => 'admin']);
        DB::table('lowongan')->insert([
            'id' => 1,
            'nama_lowongan' => 'Operator Produksi',
            'status_sim_b2' => 0,
            'status_sio' => 0,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDay(),
        ]);

        $lamaranLayak = $this->createLamaranForAssessment('Tes Kesehatan');
        $lamaranTidakLayak = $this->createLamaranForAssessment('Tes Kesehatan');
        $lamaranMenunggu = $this->createLamaranForAssessment('Tes Kesehatan');
        Lamaran::query()->whereIn('id', [$lamaranLayak->id, $lamaranTidakLayak->id, $lamaranMenunggu->id])->update(['loker_id' => 1]);

        $oldLink = $this->createHealthAssessmentLink($admin, 'old-health-link');
        $latestLink = $this->createHealthAssessmentLink($admin, 'latest-health-link');
        AssessmentLinkCandidate::create(['assessment_link_id' => $oldLink->id, 'lamaran_id' => $lamaranLayak->id, 'result_values' => ['health_status' => 'Tidak Sehat']]);
        AssessmentLinkCandidate::create(['assessment_link_id' => $latestLink->id, 'lamaran_id' => $lamaranLayak->id, 'result_values' => ['health_status' => 'Sehat']]);
        AssessmentLinkCandidate::create(['assessment_link_id' => $latestLink->id, 'lamaran_id' => $lamaranTidakLayak->id, 'result_values' => ['health_status' => 'Tidak Sehat']]);
        AssessmentLinkCandidate::create(['assessment_link_id' => $latestLink->id, 'lamaran_id' => $lamaranMenunggu->id]);

        $controller = app(LowonganController::class);
        $allResponse = $controller->directToLamaran(Request::create('/admin/lowongan/pendaftar/1', 'GET'), 1);
        $allLamarans = $allResponse->getData()['lamarans']->keyBy('id');

        $this->assertSame('eligible', $allLamarans[$lamaranLayak->id]->assessment_eligibility_status);
        $this->assertSame('kesehatan', $allLamarans[$lamaranLayak->id]->assessment_type);
        $this->assertSame($latestLink->id, $allLamarans[$lamaranLayak->id]->assessment_link_id);
        $this->assertSame('ineligible', $allLamarans[$lamaranTidakLayak->id]->assessment_eligibility_status);
        $this->assertSame('pending', $allLamarans[$lamaranMenunggu->id]->assessment_eligibility_status);

        $eligibleResponse = $controller->directToLamaran(Request::create('/admin/lowongan/pendaftar/1?assessment_eligibility=eligible', 'GET'), 1);
        $this->assertSame([$lamaranLayak->id], $eligibleResponse->getData()['lamarans']->pluck('id')->all());
    }

    public function test_lowongan_pendaftar_prefers_active_hris_position_and_otherwise_uses_latest_resignation()
    {
        config(['database.connections.mysql_hris' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('mysql_hris');

        foreach (['master_provinsi', 'master_kabupaten', 'master_kecamatan', 'master_kelurahan'] as $table) {
            Schema::connection('mysql_hris')->create($table, function (Blueprint $table) {
                $table->id();
            });
        }

        Schema::connection('mysql_hris')->create('employees', function (Blueprint $table) {
            $table->string('nik_karyawan')->primary();
            $table->string('no_ktp')->nullable();
            $table->string('nama_karyawan')->nullable();
            $table->date('tgl_resign')->nullable();
            $table->string('alasan_resign')->nullable();
            $table->string('posisi')->nullable();
            $table->string('status_resign')->nullable();
            $table->string('area_kerja')->nullable();
        });

        DB::connection('mysql_hris')->table('employees')->insert([
            [
                'nik_karyawan' => 'HRIS-RESIGNED',
                'no_ktp' => 'KTP-HRIS-POSITION',
                'nama_karyawan' => 'Kandidat HRIS',
                'tgl_resign' => '2026-06-01',
                'posisi' => 'Supervisor Lama',
                'status_resign' => 'Resign',
            ],
            [
                'nik_karyawan' => 'HRIS-ACTIVE',
                'no_ktp' => 'KTP-HRIS-POSITION',
                'nama_karyawan' => 'Kandidat HRIS',
                'tgl_resign' => null,
                'posisi' => 'Operator Aktif',
                'status_resign' => 'Aktif',
            ],
            [
                'nik_karyawan' => 'HRIS-OLDER',
                'no_ktp' => 'KTP-HRIS-RESIGNED',
                'nama_karyawan' => 'Kandidat Resign',
                'tgl_resign' => '2025-01-01',
                'posisi' => 'Operator Lama',
                'status_resign' => 'Resign',
            ],
            [
                'nik_karyawan' => 'HRIS-LATEST',
                'no_ktp' => 'KTP-HRIS-RESIGNED',
                'nama_karyawan' => 'Kandidat Resign',
                'tgl_resign' => '2026-02-01',
                'posisi' => 'Supervisor Terbaru',
                'status_resign' => 'Resign',
            ],
            [
                'nik_karyawan' => 'HRIS-INVALID-DATE',
                'no_ktp' => 'KTP-HRIS-RESIGNED',
                'nama_karyawan' => 'Kandidat Resign',
                'tgl_resign' => 'tanggal tidak valid',
                'posisi' => 'Posisi Tidak Valid',
                'status_resign' => 'Resign',
            ],
        ]);

        DB::table('lowongan')->insert([
            'id' => 1,
            'nama_lowongan' => 'Operator Produksi',
            'status_sim_b2' => 0,
            'status_sio' => 0,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDay(),
        ]);
        $user = User::create(['name' => 'Kandidat HRIS', 'email' => 'hris-position@example.test', 'password' => 'secret']);
        $lamaran = Lamaran::create([
            'biodata_id' => Biodata::create(['user_id' => $user->id, 'no_ktp' => 'KTP-HRIS-POSITION'])->id,
            'user_id' => $user->id,
            'loker_id' => 1,
        ]);
        $userResign = User::create(['name' => 'Kandidat Resign', 'email' => 'hris-resigned-position@example.test', 'password' => 'secret']);
        $lamaranResign = Lamaran::create([
            'biodata_id' => Biodata::create(['user_id' => $userResign->id, 'no_ktp' => 'KTP-HRIS-RESIGNED'])->id,
            'user_id' => $userResign->id,
            'loker_id' => 1,
        ]);

        $response = app(LowonganController::class)->directToLamaran(Request::create('/admin/lowongan/pendaftar/1', 'GET'), 1);

        $lamarans = $response->getData()['lamarans'];
        $lamaranAktif = $lamarans->firstWhere('id', $lamaran->id);

        $this->assertTrue($lamaranAktif->biodata->relationLoaded('getRiwayatInHris'));
        $this->assertSame('Operator Aktif', $lamaranAktif->biodata->latest_hris_position);
        $this->assertSame('Supervisor Terbaru', $lamarans->firstWhere('id', $lamaranResign->id)->biodata->latest_hris_position);
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
            $table->boolean('status_sim_b2')->default(false);
            $table->boolean('status_sio')->default(false);
            $table->timestamp('tanggal_mulai')->nullable();
            $table->timestamp('tanggal_berakhir')->nullable();
            $table->timestamps();
        });

        Schema::create('surat_peringatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    private function createLamaranForAssessment(string $status): Lamaran
    {
        $suffix = (string) (Lamaran::query()->count() + 1);
        $user = User::create([
            'name' => 'Kandidat Eligibility '.$suffix,
            'email' => 'eligibility-candidate-'.$suffix.'@example.test',
            'password' => 'secret',
        ]);

        return Lamaran::create([
            'biodata_id' => Biodata::create(['user_id' => $user->id, 'no_ktp' => 'KTP-ELIGIBILITY-'.$suffix])->id,
            'user_id' => $user->id,
            'status_proses' => $status,
        ]);
    }

    private function createHealthAssessmentLink(User $admin, string $token): AssessmentLink
    {
        return AssessmentLink::create([
            'assessment_type' => 'kesehatan',
            'public_token' => str_pad($token, 64, 'x'),
            'pin_hash' => Hash::make('123456'),
            'form_schema' => [['id' => 'health_status', 'label' => 'Hasil tes kesehatan', 'type' => 'select', 'required' => true, 'options' => ['Sehat', 'Tidak Sehat']]],
            'created_by' => $admin->id,
            'expires_at' => now('Asia/Makassar')->addDay(),
            'is_active' => true,
        ]);
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
