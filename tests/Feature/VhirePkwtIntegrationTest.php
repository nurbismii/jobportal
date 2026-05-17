<?php

namespace Tests\Feature;

use App\Jobs\SyncContractSignatureStatusToHris;
use App\Models\Biodata;
use App\Models\User;
use App\Models\VhirePkwtContract;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VhirePkwtIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-16 10:00:00'));

        config([
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'recruitment.internal_api.token' => 'test-token',
            'recruitment.hris_api.base_url' => null,
            'recruitment.hris_api.token' => null,
        ]);

        DB::purge('testing');
        DB::setDefaultConnection('testing');

        $this->createBaseSchema();
        include_once database_path('migrations/2026_05_16_000000_create_vhire_pkwt_integration_tables.php');
        (new \CreateVhirePkwtIntegrationTables())->up();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_hris_contract_import_is_idempotent_and_masks_no_ktp_in_response()
    {
        $payload = $this->contractPayload();

        $first = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/vhire/contracts', $payload);

        $first->assertOk()
            ->assertJsonPath('contract.no_ktp_masked', '1234********3456')
            ->assertJsonPath('contract.visible_in_vhire', true);

        $this->assertStringNotContainsString('1234567890123456', $first->getContent());
        $this->assertDatabaseCount('vhire_pkwt_contracts', 1);
        $this->assertDatabaseHas('vhire_pkwt_contracts', [
            'kode_kontrak' => 'PKWT-001',
            'duration_value' => 3,
            'duration_unit' => 'month',
        ]);
        $this->assertSame('2026-07-31', VhirePkwtContract::first()->tanggal_akhir_kontrak->format('Y-m-d'));

        $second = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/vhire/contracts', array_merge($payload, [
                'jabatan' => 'Operator Produksi Senior',
            ]));

        $second->assertOk();
        $this->assertDatabaseCount('vhire_pkwt_contracts', 1);
        $this->assertDatabaseHas('vhire_pkwt_contracts', [
            'kode_kontrak' => 'PKWT-001',
            'jabatan' => 'Operator Produksi Senior',
        ]);
        $this->assertDatabaseHas('vhire_integration_audit_logs', [
            'event' => 'pkwt_contract_imported_updated',
        ]);
    }

    public function test_hris_activation_hides_contract_from_vhire_without_deleting_it()
    {
        VhirePkwtContract::create($this->contractRecord([
            'visible_in_vhire' => true,
        ]));

        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/vhire/candidates/LAMARAN-1/activated', [
                'candidate_code' => 'VHIRE-CAND-1',
                'no_ktp' => '1234567890123456',
                'employee_nik' => 'EMP-0001',
            ]);

        $response->assertOk()
            ->assertJsonPath('hidden_contracts', 1);

        $this->assertDatabaseCount('vhire_pkwt_contracts', 1);
        $this->assertDatabaseHas('vhire_pkwt_contracts', [
            'employee_nik' => 'EMP-0001',
            'visible_in_vhire' => 0,
            'hidden_reason' => 'Kandidat sudah aktif sebagai karyawan HRIS',
            'activated_as_employee_at' => '2026-05-16 10:00:00',
        ]);
        $this->assertDatabaseHas('vhire_integration_audit_logs', [
            'event' => 'candidate_activated_as_employee',
        ]);
    }

    public function test_candidate_can_sign_visible_electronic_contract()
    {
        Queue::fake();
        $user = $this->createCandidateUser();
        $contract = VhirePkwtContract::create($this->contractRecord([
            'signature_status' => 'waiting_signature',
            'visible_in_vhire' => true,
        ]));

        $this->actingAs($user)
            ->post(route('kontrak-pkwt.sign', $contract->id), [
                'agreement' => '1',
            ])
            ->assertRedirect(route('kontrak-pkwt.index'));

        $this->assertDatabaseHas('vhire_pkwt_contracts', [
            'id' => $contract->id,
            'signature_status' => 'signed',
            'status_tanda_tangan' => 'signed',
            'signed_by_source' => 'vhire',
            'signed_at' => '2026-05-16 10:00:00',
        ]);
        $this->assertDatabaseHas('vhire_integration_audit_logs', [
            'event' => 'pkwt_contract_signed_electronically',
        ]);
        Queue::assertPushed(SyncContractSignatureStatusToHris::class);
    }

    public function test_invalid_no_ktp_is_rejected_on_contract_import()
    {
        $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/vhire/contracts', array_merge($this->contractPayload(), [
                'no_ktp' => '1234',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('no_ktp');

        $this->assertDatabaseCount('vhire_pkwt_contracts', 0);
    }

    private function createBaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('no_ktp', 32)->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('user');
            $table->unsignedTinyInteger('status_akun')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('biodata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('no_ktp', 32)->nullable();
            $table->timestamps();
        });
    }

    private function createCandidateUser(): User
    {
        $user = User::create([
            'no_ktp' => '1234567890123456',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => 'secret',
            'role' => 'user',
            'status_akun' => 1,
            'email_verified_at' => now(),
        ]);

        Biodata::create([
            'user_id' => $user->id,
            'no_ktp' => '1234567890123456',
        ]);

        return $user;
    }

    private function contractPayload(array $overrides = []): array
    {
        return array_merge([
            'hris_contract_id' => 'HRIS-CONTRACT-1',
            'vhire_candidate_id' => 'LAMARAN-1',
            'candidate_code' => 'VHIRE-CAND-1',
            'no_ktp' => '1234567890123456',
            'nama' => 'Budi Santoso',
            'kode_kontrak' => 'PKWT-001',
            'no_pkwt' => 'NO/PKWT/001',
            'jabatan' => 'Operator Produksi',
            'tanggal_mulai_kontrak' => '2026-05-01',
            'duration_value' => 3,
            'duration_unit' => 'month',
            'signature_status' => 'waiting_signature',
            'signing_method' => 'electronic',
        ], $overrides);
    }

    private function contractRecord(array $overrides = []): array
    {
        return array_merge([
            'hris_contract_id' => 'HRIS-CONTRACT-1',
            'vhire_candidate_id' => 'LAMARAN-1',
            'candidate_code' => 'VHIRE-CAND-1',
            'no_ktp' => '1234567890123456',
            'nama' => 'Budi Santoso',
            'kode_kontrak' => 'PKWT-001',
            'no_pkwt' => 'NO/PKWT/001',
            'jabatan' => 'Operator Produksi',
            'tanggal_mulai_kontrak' => '2026-05-01',
            'tanggal_akhir_kontrak' => '2026-07-31',
            'duration_value' => 3,
            'duration_unit' => 'month',
            'durasi_kontrak' => '3 bulan',
            'signature_status' => 'waiting_signature',
            'status_tanda_tangan' => 'waiting_signature',
            'signing_method' => 'electronic',
            'visible_in_vhire' => true,
        ], $overrides);
    }
}
