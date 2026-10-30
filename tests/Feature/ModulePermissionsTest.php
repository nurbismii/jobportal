<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModulePermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('no_ktp')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->integer('status_akun')->default(1);
            $table->string('rekomendasi')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_06_000000_add_module_permissions_to_users.php'))->up();
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('pengumuman');
            $table->string('thumbnail')->nullable();
            $table->text('keterangan');
            $table->timestamps();
        });
    }

    private function account(string $role = 'manager', array $permissions = []): User
    {
        $user = new User([
            'name' => 'Internal Test', 'no_ktp' => (string) (7401010101900000 + User::count()),
            'email' => User::count() . '@example.test', 'password' => Hash::make('secret123'),
            'role' => $role, 'status_akun' => 1,
        ]);
        $user->forceFill(['module_permissions' => $permissions])->save();

        return $user;
    }

    public function test_read_only_can_view_but_cannot_create_update_delete_or_use_ajax_and_bulk_actions(): void
    {
        $user = $this->account('supervisor', ['pengumuman' => ['view'], 'pengguna' => ['view'], 'lamaran' => ['view']]);
        $this->actingAs($user)->get(route('pengumumans.index'))->assertOk()->assertDontSee('Tambah Pengumuman');
        $this->get(route('pengumumans.create'))->assertForbidden();
        $this->post(route('pengumumans.store'), [])->assertForbidden();
        $this->patch(route('pengumumans.update', 1), [])->assertForbidden();
        $this->delete(route('pengumumans.destroy', 1))->assertForbidden();
        $this->postJson(route('data.autoUpdate'), ['model' => 'user', 'id' => $user->id, 'field' => 'rekomendasi', 'value' => 'Changed'])->assertForbidden();
        $this->postJson(route('user.updateStatusAkun'), ['id' => $user->id, 'status_akun' => 0])->assertForbidden();
        $this->post(route('lamaran.updateStatusMassal'), [])->assertForbidden();
        $this->post(route('import.status-lamaran'), [])->assertForbidden();
    }

    public function test_permissions_are_independent_per_module_and_delete_does_not_require_update(): void
    {
        $user = $this->account('manager', ['pengumuman' => ['view', 'delete']]);
        DB::table('pengumuman')->insert(['id' => 1, 'pengumuman' => 'Test', 'keterangan' => 'Test']);
        $this->actingAs($user)->get(route('pengguna.index'))->assertForbidden();
        $this->patch(route('pengumumans.update', 1), [])->assertForbidden();
        $this->delete(route('pengumumans.destroy', 1))->assertRedirect();
        $this->assertDatabaseMissing('pengumuman', ['id' => 1]);
    }

    public function test_only_administrator_can_create_accounts_and_permissions_are_validated(): void
    {
        $manager = $this->account('manager', ['pengguna' => ['view', 'update', 'delete']]);
        $this->actingAs($manager)->get(route('internal-accounts.index'))->assertForbidden();
        $this->post(route('internal-accounts.store'), [])->assertForbidden();
        $admin = $this->account('admin');
        $payload = ['name' => 'Supervisor', 'no_ktp' => '7401010101900020', 'email' => 'supervisor@example.test', 'role' => 'supervisor', 'status_akun' => 1, 'password' => 'password123', 'password_confirmation' => 'password123', 'permissions' => ['lamaran' => ['view', 'update']]];
        $this->actingAs($admin)->post(route('internal-accounts.store'), $payload)->assertRedirect(route('internal-accounts.index'));
        $account = User::where('email', $payload['email'])->firstOrFail();
        $this->assertTrue(Hash::check('password123', $account->password));
        $this->assertSame(['lamaran' => ['view', 'update']], $account->module_permissions);
        $payload['email'] = 'other@example.test';
        $payload['no_ktp'] = '7401010101900021';
        $payload['permissions'] = ['lamaran' => ['update']];
        $this->post(route('internal-accounts.store'), $payload)->assertSessionHasErrors('permissions.lamaran');
        $payload['permissions'] = ['unknown' => ['view']];
        $this->post(route('internal-accounts.store'), $payload)->assertSessionHasErrors('permissions');
        $payload['permissions'] = [];
        $payload['role'] = 'admin';
        $this->post(route('internal-accounts.store'), $payload)->assertSessionHasErrors('role');
    }

    public function test_revoking_permissions_and_disabling_account_apply_to_existing_sessions(): void
    {
        $admin = $this->account('admin');
        $manager = $this->account('manager', ['pengumuman' => ['view']]);
        $this->actingAs($manager)->get(route('pengumumans.index'))->assertOk();
        $this->actingAs($admin)->put(route('internal-accounts.update', $manager), [
            'name' => $manager->name, 'no_ktp' => $manager->no_ktp, 'email' => $manager->email,
            'role' => 'manager', 'status_akun' => 1,
        ])->assertRedirect();
        $this->actingAs($manager->fresh())->get(route('pengumumans.index'))->assertForbidden();
        $this->get(route('internal-accounts.landing'))->assertOk();
        $manager->update(['status_akun' => 0]);
        $this->actingAs($manager->fresh())->get(route('internal-accounts.landing'))->assertForbidden();
    }

    public function test_applicant_endpoints_cannot_modify_internal_accounts_even_with_module_permission(): void
    {
        $manager = $this->account('manager', ['pengguna' => ['view', 'update', 'delete']]);
        $admin = $this->account('admin');
        $this->actingAs($manager)->postJson(route('user.updateStatusAkun'), ['id' => $admin->id, 'status_akun' => 0])->assertUnprocessable();
        $this->postJson(route('data.autoUpdate'), ['model' => 'user', 'id' => $admin->id, 'field' => 'rekomendasi', 'value' => 'Changed'])->assertForbidden();
        $this->get(route('pengguna.edit', $admin))->assertNotFound();
        $this->actingAs($admin)->get(route('internal-accounts.edit', $admin))->assertNotFound();
        $this->assertSame(1, $admin->fresh()->status_akun);
    }

    public function test_allowed_ajax_update_uses_the_target_module_and_unknown_routes_are_denied(): void
    {
        $manager = $this->account('manager', ['pengguna' => ['view', 'update']]);
        $applicant = $this->account('user');
        $this->actingAs($manager)->postJson(route('data.autoUpdate'), ['model' => 'user', 'id' => $applicant->id, 'field' => 'rekomendasi', 'value' => 'HR'])->assertOk();
        $this->assertSame('HR', $applicant->fresh()->rekomendasi);
        $this->postJson(route('data.autoUpdate'), ['model' => 'lamaran', 'id' => 1, 'field' => 'rekomendasi', 'value' => 'HR'])->assertForbidden();
        $route = new \Illuminate\Routing\Route('GET', 'admin/new-module', ['uses' => 'App\\Http\\Controllers\\Admin\\NewController@index', 'middleware' => ['redirect.role']]);
        $this->assertNull(AdminAccess::requirement($route));
    }

    public function test_admin_pages_render_and_applicants_cannot_access_internal_modules(): void
    {
        $admin = $this->account('admin');
        $manager = $this->account();
        $this->actingAs($admin)->get(route('internal-accounts.index'))->assertOk();
        $this->get(route('internal-accounts.create'))->assertOk()->assertSee('Izin per modul');
        $this->get(route('internal-accounts.edit', $manager))->assertOk();
        $this->actingAs($this->account('user'))->get(route('internal-accounts.index'))->assertRedirect('/');
    }

    public function test_each_existing_admin_route_has_a_known_permission_and_login_uses_internal_landing(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (in_array('redirect.role', $route->middleware(), true) && !str_starts_with($route->getName() ?? '', 'internal-accounts.')) {
                $this->assertNotNull(AdminAccess::requirement($route), $route->uri());
            }
        }
        $manager = $this->account();
        $this->post(route('login'), ['email' => $manager->email, 'password' => 'secret123'])
            ->assertRedirect(route('internal-accounts.landing'));
    }

    public function test_lamaran_can_be_opened_without_lowongan_permission(): void
    {
        Schema::create('lowongan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lowongan');
        });
        DB::table('lowongan')->insert(['nama_lowongan' => 'Operator']);
        $this->actingAs($this->account('manager', ['lamaran' => ['view']]))
            ->get(route('lamarans.index'))->assertOk()->assertSee('Operator');
        $this->get(route('lowongan.index'))->assertForbidden();
    }

    public function test_hr_and_admin_can_view_all_stages_for_one_application(): void
    {
        Schema::create('lowongan', function (Blueprint $table) {
            $table->id(); $table->string('nama_lowongan');
        });
        Schema::create('biodata', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('no_ktp');
        });
        Schema::create('lamaran', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('biodata_id')->nullable(); $table->unsignedBigInteger('loker_id')->nullable();
            $table->integer('status_lamaran'); $table->string('status_proses'); $table->timestamps();
        });
        Schema::create('riwayat_proses_lamaran', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('lamaran_id');
            foreach (['status_proses', 'status_lolos', 'tanggal_proses', 'jam', 'tempat', 'pesan'] as $column) $table->string($column)->nullable();
            $table->timestamps();
        });
        $candidate = $this->account('user');
        $candidate->update(['name' => 'Pelamar Uji']);
        DB::table('lowongan')->insert(['id' => 1, 'nama_lowongan' => 'Operator']);
        DB::table('biodata')->insert(['id' => 1, 'user_id' => $candidate->id, 'no_ktp' => $candidate->no_ktp]);
        DB::table('lamaran')->insert([
            ['id' => 1, 'biodata_id' => 1, 'loker_id' => 1, 'status_lamaran' => 0, 'status_proses' => 'Tidak Lolos Tes Kesehatan'],
            ['id' => 2, 'biodata_id' => 1, 'loker_id' => 1, 'status_lamaran' => 1, 'status_proses' => 'Awal'],
        ]);
        foreach (['Awal' => null, 'Verifikasi Berkas' => 'Lolos', 'Tidak Lolos Tes Kesehatan' => 'Tidak Lolos'] as $status => $hasil) {
            DB::table('riwayat_proses_lamaran')->insert([
                'lamaran_id' => 1, 'status_proses' => $status, 'status_lolos' => $hasil,
                'tanggal_proses' => '2026-10-20', 'jam' => '08:30:00', 'tempat' => 'Kantor HR',
                'pesan' => '<p>Catatan HR</p><script>unsafeHistory()</script>', 'created_at' => '2026-10-20 08:30:00',
            ]);
        }
        DB::table('riwayat_proses_lamaran')->insert(['lamaran_id' => 2, 'status_proses' => 'Riwayat lamaran lain']);

        foreach ([$this->account('manager', ['lamaran' => ['view']]), $this->account('admin')] as $viewer) {
            $this->actingAs($viewer)->get(route('lamarans.show', 1))
                ->assertOk()->assertSee('Tahapan Proses Pelamar')->assertSee('Pelamar Uji')->assertSee($candidate->no_ktp)
                ->assertSee('Operator')->assertSee('Rekrutmen Selesai')->assertSee('08:30:00')->assertSee('Kantor HR')
                ->assertSee('<p>Catatan HR</p>', false)->assertDontSee('unsafeHistory()')
                ->assertSeeInOrder(['Riwayat Tahapan Proses', 'Tidak Lolos Tes Kesehatan', 'Verifikasi Berkas', 'Awal'])
                ->assertDontSee('Riwayat lamaran lain')
                ->assertViewHas('lamaran', fn ($lamaran) => $lamaran->riwayatProsesLamaran->pluck('status_lolos')->all() === ['Tidak Lolos', 'Lolos', null]);
        }
        $this->get(route('lamarans.show', 999))->assertNotFound();
        DB::table('riwayat_proses_lamaran')->where('lamaran_id', 1)->delete();
        DB::table('lamaran')->where('id', 1)->update(['biodata_id' => null, 'loker_id' => null]);
        $this->get(route('lamarans.show', 1))->assertOk()->assertSee('Belum ada riwayat proses lamaran.');

        $this->actingAs($this->account('supervisor', ['pengguna' => ['view'], 'lamaran' => ['view']]))
            ->get(route('pengguna.show', $candidate))->assertOk()
            ->assertSee(route('lamarans.show', 2), false)->assertSee('Lihat Tahapan');
        $this->actingAs($this->account('supervisor', ['pengguna' => ['view']]))
            ->get(route('pengguna.show', $candidate))->assertOk()->assertDontSee('Lihat Tahapan');
    }

    public function test_application_history_requires_an_active_internal_account_with_lamaran_view_permission(): void
    {
        $this->get(route('lamarans.show', 1))->assertRedirect(route('login'));
        $this->actingAs($this->account('user'))->get(route('lamarans.show', 1))->assertRedirect('/');
        $this->actingAs($this->account('manager', ['pengguna' => ['view']]))->get(route('lamarans.show', 1))->assertForbidden();
        $disabled = $this->account('manager', ['lamaran' => ['view']]);
        $disabled->update(['status_akun' => 0]);
        $this->actingAs($disabled)->get(route('lamarans.show', 1))->assertForbidden();
    }

    public function test_granted_update_permission_opens_the_form_and_saves_changes(): void
    {
        DB::table('pengumuman')->insert(['id' => 1, 'pengumuman' => 'Before', 'keterangan' => 'Before']);
        $this->actingAs($this->account('supervisor', ['pengumuman' => ['view', 'update']]))
            ->get(route('pengumumans.edit', 1))->assertOk()->assertSee('data-permission-allowed="1"', false);
        $this->patch(route('pengumumans.update', 1), ['pengumuman' => 'After', 'keterangan' => 'Updated'])
            ->assertRedirect();
        $this->assertDatabaseHas('pengumuman', ['id' => 1, 'pengumuman' => 'After']);
    }

    public function test_internal_home_shows_only_allowed_module_cards_and_an_empty_state(): void
    {
        $this->actingAs($this->account('supervisor', ['lamaran' => ['view']]))
            ->get(route('internal-accounts.landing'))->assertOk()
            ->assertSee('aria-label="Buka Lamaran"', false)->assertSee('Hanya baca')
            ->assertDontSee('aria-label="Buka Lowongan"', false)->assertDontSee('Kelola akun dan izin');
        $this->actingAs($this->account())->get(route('internal-accounts.landing'))
            ->assertOk()->assertSee('Belum ada akses modul');
        $this->actingAs($this->account('admin'))->get(route('internal-accounts.landing'))
            ->assertOk()->assertSee('Kelola akun dan izin')->assertSee('aria-label="Buka Lowongan"', false);
    }
}
