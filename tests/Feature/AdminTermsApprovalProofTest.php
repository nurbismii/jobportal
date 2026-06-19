<?php

namespace Tests\Feature;

use App\Models\Biodata;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminTermsApprovalProofTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));

        config([
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('testing');
        DB::setDefaultConnection('testing');

        $this->createDatabaseSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_view_terms_approval_proof_for_approved_user()
    {
        $admin = $this->createAdmin();
        $candidate = $this->createCandidate();

        Biodata::create([
            'user_id' => $candidate->id,
            'no_ktp' => '7401010101900001',
            'status_pernyataan' => '<div class="header"><h1>Syarat Rekrutmen</h1></div><p>Isi syarat yang disetujui.</p>',
            'syarat_ketentuan_id' => 1,
            'status_pernyataan_disetujui_pada' => Carbon::parse('2026-06-15 09:30:00'),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('pengguna.syarat-ketentuan.show', $candidate->id));

        $response->assertOk()
            ->assertSee('Bukti Persetujuan Syarat dan Ketentuan')
            ->assertSee('Budi Santoso')
            ->assertSee('7401010101900001')
            ->assertSee('15/06/2026 09:30')
            ->assertSee('<p>Isi syarat yang disetujui.</p>', false);
    }

    public function test_admin_is_redirected_when_user_has_not_approved_terms()
    {
        $admin = $this->createAdmin();
        $candidate = $this->createCandidate();

        Biodata::create([
            'user_id' => $candidate->id,
            'no_ktp' => '7401010101900001',
            'status_pernyataan' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('pengguna.syarat-ketentuan.show', $candidate->id))
            ->assertRedirect(route('pengguna.show', $candidate->id));
    }

    private function createDatabaseSchema(): void
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
            $table->longText('status_pernyataan')->nullable();
            $table->unsignedBigInteger('syarat_ketentuan_id')->nullable();
            $table->timestamp('status_pernyataan_disetujui_pada')->nullable();
            $table->timestamps();
        });
    }

    private function createAdmin(): User
    {
        return User::create([
            'no_ktp' => '9999999999999999',
            'name' => 'Admin V-Hire',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'role' => 'admin',
            'status_akun' => 1,
            'email_verified_at' => now(),
        ]);
    }

    private function createCandidate(): User
    {
        return User::create([
            'no_ktp' => '7401010101900001',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => 'secret',
            'role' => 'user',
            'status_akun' => 1,
            'email_verified_at' => now(),
        ]);
    }
}
