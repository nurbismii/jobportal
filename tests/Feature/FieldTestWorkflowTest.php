<?php

namespace Tests\Feature;

use App\Models\AssessmentLink;
use App\Models\AssessmentLinkCandidate;
use App\Models\AssessmentResultAudit;
use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\User;
use App\Services\AssessmentLinkService;
use App\Services\FieldTestService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FieldTestWorkflowTest extends TestCase
{
    private AssessmentLink $link;
    private AssessmentLinkCandidate $candidate;
    private User $admin;
    private array $signers;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'role', 'no_ktp'] as $column) $table->string($column)->nullable();
            $table->integer('status_akun')->default(1);
            $table->json('module_permissions')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('biodata', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('user_id'); $table->string('no_ktp')->nullable(); $table->timestamps(); });
        Schema::create('lamaran', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('biodata_id'); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('loker_id')->nullable(); $table->string('status_proses'); $table->timestamps(); });
        Schema::create('lowongan', function (Blueprint $table) { $table->id(); $table->string('nama_lowongan'); $table->timestamps(); });
        require_once database_path('migrations/2026_07_13_000000_create_assessment_link_tables.php');
        (new \CreateAssessmentLinkTables)->up();
        (require database_path('migrations/2026_09_14_020000_add_encrypted_pin_to_assessment_links.php'))->up();
        (require database_path('migrations/2026_09_14_030000_add_pin_version_to_assessment_links.php'))->up();
        (require database_path('migrations/2026_11_11_000000_add_field_test_workflow.php'))->up();
        Storage::fake('assessment_private');
        $this->admin = User::create(['name' => 'HR Sintetis', 'email' => 'hr@example.test', 'role' => 'admin', 'status_akun' => 1, 'password' => Hash::make('test-password')]);
        $this->signers = ['assessor_1' => ['name' => 'Penilai 1 Sintetis'], 'assessor_2' => ['name' => 'Penilai 2 Sintetis'], 'division_head' => ['name' => 'Penanggung Jawab Sintetis']];
        $biodata = Biodata::create(['user_id' => $this->admin->id, 'no_ktp' => '0000000000000000']);
        $lamaran = Lamaran::create(['biodata_id' => $biodata->id, 'user_id' => $this->admin->id, 'status_proses' => 'Tes Lapangan']);
        $this->link = app(AssessmentLinkService::class)->create($this->attributes(), [$lamaran->id], $this->admin->id);
        $this->candidate = $this->link->candidates()->firstOrFail();
        $this->unlock();
    }

    public function test_admin_creates_versioned_template_and_mobile_form_uses_approved_weights(): void
    {
        $this->actingAs($this->admin)->post(route('assessment-links.store'), $this->attributes() + ['selected_ids' => [$this->candidate->lamaran_id]])->assertSessionHasNoErrors()->assertRedirect();
        $created = AssessmentLink::latest('id')->firstOrFail();
        $this->assertSame(100, array_sum(array_column($created->field_test_config['sections'], 'max')));
        $this->get(route('assessment-links.create'))->assertOk()->assertSee('Form tes lapangan alat berat')
            ->assertSee('field_test[signers][assessor_1][name]', false)->assertDontSee('field_test[signers][assessor_1][pin]', false)->assertDontSee('Atur PIN pada halaman Akun Internal');
        auth()->logout();
        $this->get(route('assessment-links.public.show', $this->link->public_token))->assertOk()->assertSee('Mulai penilaian');
        $this->get($this->url('show'))->assertOk()->assertSee('Periksa kandidat')->assertSee('Gunakan nama sebagai tanda tangan')->assertDontSee('signer-pin')->assertDontSee('revision-pin');
        $templates = app(FieldTestService::class)->templates();
        foreach (['excavator_loader' => [20, 40, 20, 20], 'mobile_crane' => [10, 60, 20, 10], 'trailer' => [20, 40, 20, 20]] as $key => $weights) {
            $this->assertSame($weights, array_column($templates[$key]['sections'], 'max'));
            $this->assertSame(100, array_sum(array_column($templates[$key]['sections'], 'max')));
        }
        $this->assertNull($templates['dump_truck']['sections']['p2h']['max']);
    }

    public function test_partial_draft_is_not_final_and_stale_device_cannot_overwrite(): void
    {
        $this->postJson($this->url('save'), ['version' => 1, 'values' => ['components' => ['p2h' => ['Tidak']]]])->assertOk()->assertJsonPath('version', 2);
        $this->candidate->refresh();
        $this->assertSame(0, $this->candidate->result_values['p2h']);
        $this->assertNull($this->candidate->last_submitted_at);
        $this->assertSame('pending', app(AssessmentLinkService::class)->eligibilityStatus($this->candidate));
        $this->postJson($this->url('save'), ['version' => 1, 'values' => ['p2h' => 10]])->assertConflict();
        $this->assertSame(0, $this->candidate->fresh()->result_values['p2h']);
        $this->postJson($this->url('save'), ['version' => 2, 'values' => ['p2h' => 21]])->assertUnprocessable();
        $this->postJson($this->url('save'), ['version' => 2, 'values' => ['unexpected' => 5]])->assertUnprocessable();
    }

    public function test_bilingual_form_keeps_original_answers_and_exports_valid_client_configuration(): void
    {
        $this->saveComplete();
        $response = $this->get($this->url('show'))->assertOk()
            ->assertSee('Ban / 轮胎')->assertSee('Ya / 是')->assertSee('Penilai 1 / 考评员1')
            ->assertSee('value="Ya"', false)->assertSee('value="Lulus"', false)
            ->assertDontSee('value="Ya / 是"', false)->assertDontSee('value="Lulus / 合格"', false);
        preg_match('/<script id="field-test-data"[^>]*>(.*?)<\/script>/s', $response->getContent(), $matches);
        $client = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Ya / 是', $client['labels']['Ya']);
        $this->assertSame($this->signers['assessor_1']['name'], $client['signerName']);
        $this->assertSame($this->url('sign'), $client['signUrl']);
        $this->assertSame('Ya', $this->candidate->fresh()->result_values['components']['p2h'][0]);
        $this->get($this->url('report'))->assertOk()->assertSee('轮胎')->assertSee('检查项目与回答')->assertSee('Lulus / 合格');
        $labels = trans('field_test', [], 'id');
        foreach (app(FieldTestService::class)->templates() as $template) {
            $this->assertArrayHasKey($template['name'], $labels);
            foreach ($template['sections'] as $section) {
                $this->assertArrayHasKey($section['label'], $labels);
                foreach ($section['indicators'] as $indicator) $this->assertArrayHasKey($indicator, $labels);
            }
        }
    }

    public function test_field_test_pin_page_is_bilingual_and_other_assessments_keep_their_labels(): void
    {
        session()->forget('assessment_link_access.'.$this->link->id);
        $url = route('assessment-links.public.show', $this->link->public_token);
        $this->get($url)->assertOk()->assertSee('访问码验证')->assertSee('Buka Formulir / 打开表格');
        $this->link->update(['assessment_type' => 'kesehatan']);
        $this->get($url)->assertOk()->assertSee('Verifikasi PIN Penilaian')->assertDontSee('访问码验证');
    }

    public function test_same_device_signs_in_sequence_and_only_third_signature_finalizes(): void
    {
        $this->saveComplete();
        $this->postJson($this->url('sign'), $this->signature('assessor_2'))->assertConflict();
        $this->postJson($this->url('sign'), array_replace($this->signature('assessor_1'), ['consent' => false]))->assertUnprocessable()->assertJsonValidationErrors('consent');
        $this->postJson($this->url('sign'), $this->signature('assessor_1'))->assertOk()->assertJsonPath('signed_count', 1);
        $auditCount = AssessmentResultAudit::count();
        $this->postJson($this->url('sign'), array_replace($this->signature('assessor_1'), ['version' => 2]))->assertOk();
        $this->assertSame($auditCount, AssessmentResultAudit::count());
        $this->assertCount(1, Storage::disk('assessment_private')->allFiles());
        $this->postJson($this->url('save'), ['version' => 3, 'values' => $this->completeValues()])->assertConflict();
        $this->postJson(route('assessment-links.public.autosave', [$this->link->public_token, $this->candidate->id]), ['values' => $this->completeValues()])->assertUnprocessable();
        $this->assertSame('pending', app(AssessmentLinkService::class)->eligibilityStatus($this->candidate->fresh()));
        $this->postJson($this->url('sign'), $this->signature('assessor_2'))->assertOk()->assertJsonPath('signed_count', 2);
        $this->postJson($this->url('sign'), $this->signature('division_head'))->assertOk()->assertJsonPath('signed_count', 3);
        $this->candidate->refresh();
        $this->assertNotNull($this->candidate->last_submitted_at);
        $this->assertSame(1, User::count());
        foreach ($this->signers as $role => $signer) {
            $this->assertNull($this->candidate->field_test_signatures[$role]['user_id']);
            $this->assertSame($signer['name'], $this->candidate->field_test_signatures[$role]['name']);
        }
        $this->assertSame('eligible', app(AssessmentLinkService::class)->eligibilityStatus($this->candidate));
        $this->assertSame('Tes Lapangan', $this->candidate->lamaran->status_proses);
        $this->get($this->url('report'))->assertOk()->assertSee('FINAL')->assertSee('data:image/png;base64,', false);
        $this->actingAs($this->admin)->get(route('assessment-links.field-test-report', [$this->link, $this->candidate->id]))->assertOk();
    }

    public function test_blank_signature_incomplete_scores_and_unassigned_role_are_rejected(): void
    {
        $this->postJson($this->url('sign'), $this->signature('assessor_1'))->assertUnprocessable();
        $this->saveComplete();
        $this->postJson($this->url('sign'), array_replace($this->signature('assessor_1'), ['signature_data' => $this->png(false)]))->assertUnprocessable();
        $this->postJson($this->url('sign'), array_replace($this->signature('assessor_1'), ['role' => 'unassigned']))->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertCount(0, Storage::disk('assessment_private')->allFiles());
    }

    public function test_revision_without_signer_pin_requires_reason_and_keeps_old_signature_in_audit(): void
    {
        $this->saveComplete();
        $this->postJson($this->url('sign'), $this->signature('assessor_1'))->assertOk();
        $old = $this->candidate->fresh()->field_test_signatures['assessor_1'];
        $data = ['version' => 3, 'role' => 'assessor_2', 'reason' => 'Perbaiki nilai pengoperasian rem'];
        $this->postJson($this->url('revise'), array_replace($data, ['reason' => '']))->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson($this->url('revise'), $data)->assertOk()->assertJsonPath('revision', 2);
        $this->candidate->refresh();
        $this->assertSame([], $this->candidate->field_test_signatures);
        $this->assertSame($old['path'], AssessmentResultAudit::latest('id')->first()->old_values['signatures']['assessor_1']['path']);
        Storage::disk('assessment_private')->assertExists($old['path']);
        $this->get($this->url('show'))->assertOk()->assertSee('Perbaiki nilai pengoperasian rem');
        $this->assertSame('pending', app(AssessmentLinkService::class)->eligibilityStatus($this->candidate));
        $this->postJson($this->url('sign'), array_replace($this->signature('assessor_1'), ['revision' => 1]))->assertConflict();
    }

    public function test_candidate_scope_expired_links_and_pin_session_are_enforced(): void
    {
        $other = $this->link->replicate(); $other->public_token = str_repeat('z', 64); $other->save();
        $foreign = AssessmentLinkCandidate::create(['assessment_link_id' => $other->id, 'lamaran_id' => $this->candidate->lamaran_id]);
        $this->get(route('field-tests.show', [$this->link->public_token, $foreign->id]))->assertNotFound();
        $this->postJson(route('field-tests.save', [$this->link->public_token, $foreign->id]), ['version' => 1, 'values' => []])->assertNotFound();
        $this->get(route('field-tests.report', [$this->link->public_token, $foreign->id]))->assertNotFound();
        session()->forget('assessment_link_access.'.$this->link->id);
        $this->get($this->url('show'))->assertForbidden();
        $this->unlock();
        $this->link->update(['is_active' => false]);
        $this->postJson($this->url('save'), ['version' => 1, 'values' => []])->assertNotFound();
    }

    public function test_setup_requires_distinct_names_and_does_not_accept_signer_credentials(): void
    {
        $input = $this->attributes();
        $input['field_test']['signers']['assessor_2'] = $this->signers['assessor_1'];
        $this->actingAs($this->admin)->post(route('assessment-links.store'), $input + ['selected_ids' => [$this->candidate->lamaran_id]])->assertSessionHasErrors();
        $this->assertArrayNotHasKey('field_test_config', $this->link->toArray());
        $this->assertSame($this->signers, $this->link->field_test_config['signers']);
        $this->get($this->url('show'))->assertOk()->assertDontSee('signer-pin');
        $input = $this->attributes();
        $input['field_test']['signers']['assessor_1']['pin'] = '654321';
        $this->post(route('assessment-links.store'), $input + ['selected_ids' => [$this->candidate->lamaran_id]])->assertSessionHasErrors();
        $this->assertNull(session()->getOldInput('field_test.signers.assessor_1.pin'));
        $this->get(route('assessment-links.show', $this->link))->assertOk()->assertSee('Laporan & tanda tangan', false);
        $this->admin->update(['role' => 'supervisor']);
        $this->actingAs($this->admin)->get(route('assessment-links.field-test-report', [$this->link, $this->candidate->id]))->assertForbidden();
    }

    private function unlock(): void
    {
        $this->withSession(['assessment_link_access.'.$this->link->id => true, 'assessment_link_pin_version.'.$this->link->id => (int) $this->link->pin_version]);
    }

    public function test_link_pin_is_still_required_while_signer_pin_is_not_required(): void
    {
        session()->forget('assessment_link_access.'.$this->link->id);
        $this->get($this->url('show'))->assertForbidden();
        $this->post(route('assessment-links.public.unlock', $this->link->public_token), ['pin' => 'wrong-pin'])->assertSessionHasErrors('pin');
        $this->get($this->url('show'))->assertForbidden();
        $this->post(route('assessment-links.public.unlock', $this->link->public_token), ['pin' => 'LinkPin123'])->assertRedirect();
        $this->get($this->url('show'))->assertOk();
        $this->saveComplete();
        $this->postJson($this->url('sign'), $this->signature('assessor_1'))->assertOk();
        $this->assertGuest();
    }

    public function test_assignment_names_are_preserved_after_invalid_input(): void
    {
        $payload = $this->attributes() + ['selected_ids' => []];
        $this->actingAs($this->admin)->post(route('assessment-links.store'), $payload)->assertSessionHasErrors('selected_ids');
        foreach ($this->signers as $role => $signer) {
            $this->assertNull(session()->getOldInput('field_test.signers.'.$role.'.pin'));
            $this->assertSame($signer['name'], session()->getOldInput('field_test.signers.'.$role.'.name'));
        }
        $payload['selected_ids'] = [$this->candidate->lamaran_id];
        $payload['field_test']['signers']['assessor_1']['name'] = '';
        $this->post(route('assessment-links.store'), $payload)->assertSessionHasErrors();
        $this->assertNull(session()->getOldInput('field_test.signers.assessor_1.pin'));
        $this->assertSame(1, User::count());
    }

    private function attributes(): array
    {
        return ['assessment_type' => 'lapangan', 'pin' => 'LinkPin123', 'expires_on' => now()->addDays(7)->toDateString(), 'field_test' => ['template' => 'excavator_loader', 'location' => 'Area tes sintetis', 'test_date' => now()->toDateString(), 'maxima' => ['p2h' => 20, 'driving' => 40, 'safety' => 20, 'maintenance' => 20], 'signers' => $this->signers]];
    }

    public function test_legacy_assignments_can_be_signed_without_an_account_or_signer_pin(): void
    {
        $legacy = User::create(['name' => 'Nama Akun Berubah', 'role' => 'supervisor', 'status_akun' => 0]);
        $legacy->forceFill(['field_test_pin_hash' => Hash::make('123987')])->save();
        $config = $this->link->field_test_config;
        $config['signers']['assessor_1'] = ['user_id' => $legacy->id, 'name' => 'Nama Saat Penugasan', 'pin_hash' => $legacy->field_test_pin_hash];
        $this->link->update(['field_test_config' => $config]);
        $this->saveComplete();
        $this->get($this->url('show'))->assertOk()->assertDontSee('123987')->assertDontSee($legacy->field_test_pin_hash)->assertDontSee('signer-pin');
        $this->postJson($this->url('sign'), $this->signature('assessor_1'))->assertOk();
        $this->assertNull($this->candidate->fresh()->field_test_signatures['assessor_1']['user_id']);
        $this->assertSame('Nama Saat Penugasan', $this->candidate->fresh()->field_test_signatures['assessor_1']['name']);
        $this->assertArrayNotHasKey('field_test_pin_hash', $legacy->toArray());
    }

    private function completeValues(): array
    {
        $components = [];
        foreach (app(FieldTestService::class)->sectionsFor($this->link) as $id => $section) {
            $components[$id] = array_fill(0, count($section['indicators']), 'Ya');
        }
        return ['sim' => 'Ada', 'ktp' => 'Ada', 'components' => $components, 'decision' => 'Lulus'];
    }

    public function test_component_scores_are_equal_weighted_and_computed_on_server(): void
    {
        $values = $this->completeValues();
        $values['components']['p2h'][5] = 'Tidak';
        $this->postJson($this->url('save'), ['version' => 1, 'values' => $values])->assertOk();
        $saved = $this->candidate->fresh()->result_values;
        $this->assertEquals(16.67, $saved['p2h']);
        $this->assertEquals(40, $saved['driving']);
        $this->assertSame(['Ban', 'Spion samping', 'Spion dalam', 'Air radiator', 'Oli hidrolik', 'Oli swing / transmisi'], $saved['component_sections']['p2h']['indicators']);
        $values['p2h'] = 20;
        $this->postJson($this->url('save'), ['version' => 2, 'values' => $values])->assertUnprocessable();
        $this->assertEquals(16.67, $this->candidate->fresh()->result_values['p2h']);
        $this->get($this->url('report'))->assertOk()->assertSee('16,67')->assertSee('5 Ya')->assertSee('Komponen dan jawaban');
    }

    public function test_unanswered_components_differ_from_no_and_block_signature(): void
    {
        $values = $this->completeValues();
        $values['components']['p2h'] = array_fill(0, 6, 'Tidak');
        unset($values['components']['driving'][0]);
        $this->postJson($this->url('save'), ['version' => 1, 'values' => $values])->assertOk();
        $saved = $this->candidate->fresh()->result_values;
        $this->assertEquals(0, $saved['p2h']);
        $this->assertNull($saved['components']['driving'][0]);
        $this->postJson($this->url('sign'), $this->signature('assessor_1'))->assertUnprocessable()->assertJsonValidationErrors('values.components.driving.0');
        $values['components']['p2h'][0] = 'true';
        $this->postJson($this->url('save'), ['version' => 2, 'values' => $values])->assertUnprocessable();
        $values['components']['p2h'][0] = 'Ya';
        $values['components']['p2h'][6] = 'Ya';
        $this->postJson($this->url('save'), ['version' => 2, 'values' => $values])->assertUnprocessable();
    }

    public function test_all_templates_use_their_own_component_counts_and_weights(): void
    {
        $service = app(FieldTestService::class);
        $counts = ['dump_truck' => [6, 6, 6], 'excavator_loader' => [6, 11, 4, 2], 'mobile_crane' => [5, 11, 4, 2], 'trailer' => [7, 11, 4, 2]];
        foreach ($service->templates() as $key => $template) {
            $this->assertSame($counts[$key], array_values(array_map(fn ($section) => count($section['indicators']), $template['sections'])));
            $input = $this->attributes()['field_test'];
            $input['template'] = $key;
            $input['maxima'] = array_map(fn ($section) => $section['max'] ?? 20, $template['sections']);
            $this->link->update(['field_test_config' => $service->configuration($input)]);
            $this->candidate->refresh()->update(['result_values' => null]);
            $values = $this->completeValues();
            $this->postJson($this->url('save'), ['version' => $this->candidate->fresh()->field_test_version, 'values' => $values])->assertOk();
            foreach ($input['maxima'] as $id => $max) $this->assertEquals($max, $this->candidate->fresh()->result_values[$id]);
        }
    }

    public function test_existing_signed_manual_results_are_preserved_and_revision_requires_checklist(): void
    {
        $config = $this->link->field_test_config;
        unset($config['scoring']);
        $config['sections']['p2h']['indicators'] = ['Ban', 'Spion samping dan dalam', 'Air radiator', 'Oli hidrolik', 'Oli swing / transmisi'];
        $this->link->update(['field_test_config' => $config]);
        $this->candidate->update(['result_values' => ['sim' => 'Ada', 'ktp' => 'Ada', 'p2h' => 15, 'driving' => 32, 'safety' => 16, 'maintenance' => 15, 'decision' => 'Lulus'], 'field_test_signatures' => ['assessor_1' => ['name' => 'Penilai Lama', 'signed_at' => now()->toIso8601String(), 'path' => 'old-signature.png']]]);
        $this->get($this->url('show'))->assertOk()->assertSee('format nilai manual sebelumnya')->assertDontSee('type="number"', false);
        $this->get($this->url('report'))->assertOk()->assertSee('15,00');
        $this->postJson($this->url('revise'), ['version' => 1, 'role' => 'assessor_2', 'reason' => 'Penilaian ulang berdasarkan komponen'])->assertOk();
        $this->get($this->url('show'))->assertOk()->assertSee('Spion dalam')->assertSee('data-index="5"', false);
        $this->postJson($this->url('sign'), $this->signature('assessor_1'))->assertUnprocessable();
        $this->postJson($this->url('save'), ['version' => 2, 'values' => $this->completeValues()])->assertOk();
        $this->assertEquals(20, $this->candidate->fresh()->result_values['p2h']);
        $this->assertCount(6, $this->candidate->fresh()->result_values['component_sections']['p2h']['indicators']);
    }

    private function saveComplete(): void
    {
        $this->postJson($this->url('save'), ['version' => 1, 'values' => $this->completeValues(), 'petugas_note' => 'Catatan sintetis'])->assertOk();
    }

    private function signature(string $role): array
    {
        $this->candidate->refresh();
        return ['version' => $this->candidate->field_test_version, 'revision' => $this->candidate->field_test_revision, 'role' => $role, 'signature_data' => $this->png(), 'consent' => true];
    }

    private function png(bool $ink = true): string
    {
        $image = imagecreatetruecolor(640, 220); imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        if ($ink) imageline($image, 20, 20, 450, 160, imagecolorallocate($image, 20, 30, 40));
        ob_start(); imagepng($image); $bytes = ob_get_clean();
        return 'data:image/png;base64,'.base64_encode($bytes);
    }

    private function url(string $action): string
    {
        return route('field-tests.'.$action, [$this->link->public_token, $this->candidate->id]);
    }
}
