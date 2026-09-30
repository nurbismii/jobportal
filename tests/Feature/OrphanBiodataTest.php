<?php

namespace Tests\Feature;

use App\Imports\ImportStatusLamaran;
use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\User;
use App\Services\LamaranStatusService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrphanBiodataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
        ]);
        DB::purge('testing');
        DB::setDefaultConnection('testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role')->default('user');
            $table->timestamp('email_verified_at')->nullable();
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
            $table->unsignedBigInteger('loker_id')->nullable();
            $table->boolean('status_lamaran')->default(false);
            $table->string('status_proses')->default('Awal');
            $table->timestamps();
        });
        Schema::create('lowongan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permintaan_tenaga_kerja_id')->nullable();
        });
        Schema::create('riwayat_proses_lamaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lamaran_id');
            foreach (['status_proses', 'status_lolos', 'tanggal_proses', 'jam', 'tempat', 'pesan'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
    }

    public function test_orphan_and_missing_biodata_are_rejected_before_update(): void
    {
        $biodata = Biodata::create(['user_id' => 999]);
        foreach ([$biodata->id, null] as $biodataId) {
            $lamaran = Lamaran::create(['biodata_id' => $biodataId]);
            try {
                app(LamaranStatusService::class)->apply($lamaran, 'aktif bekerja');
                $this->fail('Update orphan seharusnya ditolak.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('biodata', $exception->errors());
            }
            $this->assertSame('Awal', $lamaran->fresh()->status_proses);
        }
        $this->assertDatabaseCount('riwayat_proses_lamaran', 0);
    }

    public function test_import_reports_orphan_row_and_continues_with_valid_row(): void
    {
        $user = User::create([]);
        $import = new ImportStatusLamaran;
        foreach ([999, $user->id] as $index => $userId) {
            $biodata = Biodata::create(['user_id' => $userId, 'no_ktp' => 'TEST-' . $index]);
            Lamaran::create(['biodata_id' => $biodata->id]);
            $import->rememberRowNumber($index + 2);
            $import->model([
                'no_ktp' => $biodata->no_ktp, 'status_tahapan' => 'tes kesehatan',
                'tanggal_proses' => '2026-09-30', 'tempat' => 'Kantor',
            ]);
        }
        $this->assertCount(1, $import->failures());
        $this->assertSame(2, $import->failures()->first()->row());
        $this->assertDatabaseHas('lamaran', ['id' => 1, 'status_proses' => 'Awal']);
        $this->assertDatabaseHas('lamaran', ['id' => 2, 'status_proses' => 'Tes Kesehatan']);
        $this->assertDatabaseHas('riwayat_proses_lamaran', ['user_id' => $user->id]);
        $this->assertDatabaseCount('riwayat_proses_lamaran', 1);

        $orphanWithoutLamaran = Biodata::create(['user_id' => 998, 'no_ktp' => 'TEST-NO-LAMARAN']);
        $import->rememberRowNumber(4);
        $import->model([
            'no_ktp' => $orphanWithoutLamaran->no_ktp, 'status_tahapan' => 'tes kesehatan',
            'tanggal_proses' => '2026-09-30', 'tempat' => 'Kantor',
        ]);
        $this->assertCount(2, $import->failures());
        $this->assertSame(4, $import->failures()->last()->row());
    }

    public function test_history_failure_rolls_back_status_update(): void
    {
        $user = User::create([]);
        $biodata = Biodata::create(['user_id' => $user->id]);
        $lamaran = Lamaran::create(['biodata_id' => $biodata->id]);
        Schema::drop('riwayat_proses_lamaran');
        try {
            app(LamaranStatusService::class)->apply($lamaran, 'tes kesehatan');
            $this->fail('Penyimpanan riwayat seharusnya gagal.');
        } catch (QueryException $exception) {
            $this->assertSame('Awal', $lamaran->fresh()->status_proses);
        }
    }

    public function test_cleanup_preserves_accounts_with_biodata(): void
    {
        $withBiodata = User::create([]);
        $expired = User::create([]);
        $recent = User::create([]);
        User::whereIn('id', [$withBiodata->id, $expired->id])->update([
            'created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2),
        ]);
        Biodata::create(['user_id' => $withBiodata->id]);
        $this->artisan('users:cleanup-unverified')->assertSuccessful();
        $this->assertNotNull($withBiodata->fresh());
        $this->assertNotNull($recent->fresh());
        $this->assertNull($expired->fresh());
    }

    public function test_model_deletion_cannot_leave_biodata_orphaned(): void
    {
        $user = User::create([]);
        Biodata::create(['user_id' => $user->id]);
        try {
            $user->delete();
            $this->fail('User dengan biodata seharusnya tidak terhapus.');
        } catch (ValidationException $exception) {
            $this->assertNotNull($user->fresh());
            $this->assertDatabaseCount('biodata', 1);
        }
    }

    public function test_audit_only_reports_orphans_without_modifying_data(): void
    {
        $user = User::create([]);
        Biodata::create(['user_id' => $user->id]);
        Biodata::create(['user_id' => 999]);
        Biodata::create(['user_id' => null]);
        $this->artisan('biodata:audit-orphans')
            ->expectsOutput('Jumlah biodata orphan: 2')->assertSuccessful();
        $this->assertDatabaseCount('biodata', 3);
    }

    public function test_foreign_key_migration_refuses_existing_orphans_without_changes(): void
    {
        Biodata::create(['user_id' => 999]);
        $migration = require database_path('migrations/2026_09_30_000000_protect_biodata_user_relation.php');
        try {
            $migration->up();
            $this->fail('Migration harus menolak orphan lama.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('1 biodata orphan', $exception->getMessage());
        }
        $this->assertDatabaseHas('biodata', ['user_id' => 999]);
        $this->assertEmpty(Schema::getForeignKeys('biodata'));
    }

    public function test_database_constraints_prevent_orphans_even_without_model_events(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $user = User::create([]);
        $biodata = Biodata::create(['user_id' => $user->id]);
        $migration = require database_path('migrations/2026_09_30_000000_protect_biodata_user_relation.php');
        $migration->up();

        foreach ([
            fn () => DB::table('users')->where('id', $user->id)->delete(),
            fn () => DB::table('users')->where('id', $user->id)->update(['id' => 999]),
            fn () => DB::table('biodata')->insert(['user_id' => 999]),
            fn () => DB::table('biodata')->insert(['user_id' => null]),
            fn () => DB::table('biodata')->where('id', $biodata->id)->update(['user_id' => 999]),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Database harus menolak operasi penyebab orphan.');
            } catch (QueryException $exception) {
                $this->assertNotNull($user->fresh());
                $this->assertSame($user->id, $biodata->fresh()->user_id);
            }
        }

        DB::table('biodata')->where('id', $biodata->id)->delete();
        $this->assertSame(1, DB::table('users')->where('id', $user->id)->delete());
        $migration->down();
        $this->assertEmpty(Schema::getForeignKeys('biodata'));
    }
}
