<?php

namespace Tests\Feature;

use App\Models\Biodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BiodataProfileMigrationTest extends TestCase
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
            $table->string('hobi')->nullable();
            $table->timestamps();
        });

        require_once database_path('migrations/2026_08_09_000000_create_biodata_minat_bakat_and_prestasi_tables.php');
        (new \CreateBiodataMinatBakatAndPrestasiTables())->up();
    }

    public function test_migration_creates_structured_profile_tables_and_relations()
    {
        $this->assertTrue(Schema::hasColumn('biodata', 'bakat'));
        $this->assertTrue(Schema::hasTable('biodata_minat_bakat'));
        $this->assertTrue(Schema::hasTable('biodata_prestasi'));

        $biodata = Biodata::create([
            'hobi' => 'Futsal, Badminton',
            'bakat' => 'Renang',
        ]);

        $biodata->minatBakat()->createMany([
            ['tipe' => 'hobi', 'kategori' => 'olahraga', 'nama' => 'Futsal'],
            ['tipe' => 'hobi', 'kategori' => 'olahraga', 'nama' => 'Badminton'],
            ['tipe' => 'bakat', 'kategori' => 'olahraga', 'nama' => 'Renang'],
        ]);
        $biodata->daftarPrestasi()->create([
            'bidang' => 'Olahraga',
            'jenis_prestasi' => 'Kejuaraan Renang',
            'peringkat' => 'Juara 1',
            'tingkat' => 'Provinsi',
            'periode' => '2025-05',
        ]);

        $this->assertCount(3, $biodata->minatBakat()->get());
        $this->assertCount(1, $biodata->daftarPrestasi()->get());

        $biodata->delete();

        $this->assertDatabaseCount('biodata_minat_bakat', 0);
        $this->assertDatabaseCount('biodata_prestasi', 0);
    }

    public function test_migration_can_be_run_again_after_schema_was_created()
    {
        (new \CreateBiodataMinatBakatAndPrestasiTables())->up();

        $this->assertTrue(Schema::hasColumn('biodata', 'bakat'));
        $this->assertTrue(Schema::hasTable('biodata_minat_bakat'));
        $this->assertTrue(Schema::hasTable('biodata_prestasi'));
    }
}
