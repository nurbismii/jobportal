<?php

namespace Tests\Feature;

use App\Models\Biodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BiodataPengalamanKerjaMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('testing');
        DB::setDefaultConnection('testing');
        DB::statement('PRAGMA foreign_keys = ON');

        Schema::create('biodata', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        require_once database_path('migrations/2026_08_09_010000_create_biodata_pengalaman_kerja_table.php');
        (new \CreateBiodataPengalamanKerjaTable())->up();
    }

    public function test_migration_creates_work_experience_relation_with_cascade_delete()
    {
        $this->assertTrue(Schema::hasTable('biodata_pengalaman_kerja'));

        $biodata = Biodata::create();
        $biodata->pengalamanKerja()->create([
            'nama_perusahaan' => 'PT Contoh',
            'posisi' => 'Operator',
            'tanggal_mulai' => '2024-01',
            'tanggal_selesai' => '2025-01',
            'masih_bekerja' => false,
            'urutan' => 1,
        ]);

        $this->assertCount(1, $biodata->pengalamanKerja()->get());

        $biodata->delete();

        $this->assertDatabaseCount('biodata_pengalaman_kerja', 0);
    }

    public function test_migration_can_be_run_again_safely()
    {
        (new \CreateBiodataPengalamanKerjaTable())->up();

        $this->assertTrue(Schema::hasTable('biodata_pengalaman_kerja'));
    }
}
