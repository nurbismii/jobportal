<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PermintaanTenagaKerjaController;
use App\Models\PermintaanTenagaKerja;
use App\Models\Lowongan;
use App\Services\Vhire\OnboardingCandidateSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class PermintaanTenagaKerjaRincianTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $connection = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config(['database.default' => 'testing', 'database.connections.testing' => $connection, 'database.connections.mysql_hris' => $connection]);
        DB::purge('testing');
        DB::purge('mysql_hris');
        DB::setDefaultConnection('testing');
        Schema::connection('mysql_hris')->create('departemens', function (Blueprint $table) {
            $table->id();
        });
        Schema::connection('mysql_hris')->create('divisis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('departemen_id');
            $table->string('nama_divisi')->nullable();
        });
        DB::connection('mysql_hris')->table('departemens')->insert(['id' => 1]);
        DB::connection('mysql_hris')->table('divisis')->insert([
            ['id' => 10, 'departemen_id' => 1],
            ['id' => 11, 'departemen_id' => 1],
            ['id' => 12, 'departemen_id' => 2],
        ]);
        Schema::create('permintaan_tenaga_kerja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('departemen_id');
            $table->unsignedBigInteger('divisi_id')->nullable();
            foreach (['no_surat_ptk', 'posisi', 'tanggal_pengajuan', 'tanggal_terima', 'jenis_kelamin', 'rentang_usia', 'background_pendidikan', 'kualifikasi_ptk', 'status_ptk'] as $field) {
                $table->string($field);
            }
            $table->unsignedInteger('jumlah_ptk');
            $table->unsignedInteger('jumlah_masuk')->default(0);
            $table->timestamps();
        });
        $this->migration()->up();
    }

    public function test_multiple_rows_are_validated_saved_and_updated_with_correct_totals(): void
    {
        $payload = $this->payload();
        $data = $this->build($this->validate($payload));
        $ptk = PermintaanTenagaKerja::create($data)->fresh();
        $this->assertCount(2, $ptk->rincian_permintaan);
        $this->assertSame(7, $ptk->jumlah_ptk);
        $this->assertSame('Operator, Mekanik', $ptk->ringkasan_posisi);
        $this->assertSame('Operator', $ptk->posisi);
        $this->assertSame('Laki-laki', $ptk->rincian_permintaan[0]['jenis_kelamin']);
        $this->assertSame('Perempuan', $ptk->rincian_permintaan[1]['jenis_kelamin']);
        $this->assertSame('Laki-laki dan Perempuan', $ptk->jenis_kelamin);
        $this->assertSame('<p>Pengalaman <strong>operator</strong></p>', $ptk->rincian_permintaan[0]['kualifikasi_ptk']);
        $this->assertSame('<p>Pengalaman mekanik</p>', $ptk->rincian_permintaan[1]['kualifikasi_ptk']);
        $payload['rincian'] = [3 => $payload['rincian'][1]];
        $ptk->update($this->build($this->validate($payload)));
        $this->assertSame(5, $ptk->fresh()->jumlah_ptk);
        $this->assertSame('Mekanik', $ptk->fresh()->rincian_permintaan[0]['posisi']);
        $this->assertSame(0, $ptk->fresh()->jumlah_masuk);
        $this->assertSame('Perempuan', $ptk->fresh()->jenis_kelamin);
    }

    public function test_legacy_records_remain_available_as_one_row(): void
    {
        $data = $this->build($this->validate($this->payload()));
        unset($data['rincian']);
        $ptk = PermintaanTenagaKerja::create($data)->fresh();
        $this->assertCount(1, $ptk->rincian_permintaan);
        $this->assertSame('Operator', $ptk->rincian_permintaan[0]['posisi']);
        $this->assertSame($data['jenis_kelamin'], $ptk->rincian_permintaan[0]['jenis_kelamin']);
        $this->assertSame($data['kualifikasi_ptk'], $ptk->rincian_permintaan[0]['kualifikasi_ptk']);
    }

    public function test_existing_json_rows_inherit_legacy_gender_without_overwriting_explicit_values(): void
    {
        $data = $this->build($this->validate($this->payload()));
        unset($data['rincian'][0]['jenis_kelamin']);
        unset($data['rincian'][0]['kualifikasi_ptk']);
        $data['jenis_kelamin'] = 'Laki-laki';
        $ptk = PermintaanTenagaKerja::create($data)->fresh();
        $this->assertSame('Laki-laki', $ptk->rincian_permintaan[0]['jenis_kelamin']);
        $this->assertSame('Perempuan', $ptk->rincian_permintaan[1]['jenis_kelamin']);
        $this->assertSame($data['kualifikasi_ptk'], $ptk->rincian_permintaan[0]['kualifikasi_ptk']);
        $this->assertSame('<p>Pengalaman mekanik</p>', $ptk->rincian_permintaan[1]['kualifikasi_ptk']);
    }

    public function test_empty_rich_text_qualifications_are_rejected(): void
    {
        foreach (['<p><br></p>', '<p>&nbsp;</p>', '<script>alert(1)</script>'] as $empty) {
            $payload = $this->payload();
            $payload['rincian'][1]['kualifikasi_ptk'] = $empty;
            try {
                $this->validate($payload);
                $this->fail('Empty qualification was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('rincian.1.kualifikasi_ptk', $exception->errors());
            }
        }
    }

    public function test_qualifications_keep_formatting_and_remove_active_html(): void
    {
        $payload = $this->payload();
        $payload['rincian'][1]['kualifikasi_ptk'] = '<p onclick="alert(1)"><strong>Pengalaman</strong><img src="x" onerror="alert(1)"><script>alert(1)</script></p>';
        $data = $this->build($this->validate($payload));
        $this->assertSame('<p><strong>Pengalaman</strong></p>', $data['rincian'][1]['kualifikasi_ptk']);
    }

    public function test_invalid_gender_is_rejected_per_row(): void
    {
        $payload = $this->payload();
        $payload['rincian'][1]['jenis_kelamin'] = 'Invalid';
        try {
            $this->validate($payload);
            $this->fail('Invalid gender was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('rincian.1.jenis_kelamin', $exception->errors());
        }
    }

    public function test_each_row_rejects_invalid_data_and_foreign_department_division(): void
    {
        $payload = $this->payload();
        $payload['rincian'][1] = ['divisi' => 12, 'posisi' => '', 'jumlah_ptk' => 0, 'rentang_usia' => '', 'background_pendidikan' => 'Invalid'];
        try {
            $this->validate($payload);
            $this->fail('Invalid row was accepted.');
        } catch (ValidationException $exception) {
            foreach (['divisi', 'posisi', 'jumlah_ptk', 'jenis_kelamin', 'rentang_usia', 'background_pendidikan', 'kualifikasi_ptk'] as $field) {
                $this->assertArrayHasKey('rincian.1.' . $field, $exception->errors());
            }
        }
    }

    public function test_at_least_one_row_is_required(): void
    {
        $payload = $this->payload();
        $payload['rincian'] = [];
        $this->expectException(ValidationException::class);
        $this->validate($payload);
    }

    public function test_division_remains_optional(): void
    {
        $payload = $this->payload();
        $payload['rincian'][0]['divisi'] = null;
        $this->assertNull($this->build($this->validate($payload))['divisi_id']);
        $payload['rincian'][1]['divisi'] = null;
        $this->assertCount(2, $this->validate($payload)['rincian']);
    }

    public function test_the_same_division_accepts_different_positions(): void
    {
        $payload = $this->payload();
        $payload['rincian'][1]['divisi'] = '10';
        $ptk = PermintaanTenagaKerja::create($this->build($this->validate($payload)))->fresh();
        $this->assertCount(2, $ptk->rincian_permintaan);
        $this->assertSame('Mekanik', $ptk->rincian_permintaan[1]['posisi']);
    }

    public function test_the_same_position_accepts_different_divisions(): void
    {
        $payload = $this->payload();
        $payload['rincian'][1]['posisi'] = 'Operator';
        $this->assertCount(2, $this->validate($payload)['rincian']);
    }

    public function test_duplicate_division_and_position_are_rejected_ignoring_case_and_spacing(): void
    {
        $payload = $this->payload();
        $payload['rincian'][1]['divisi'] = '10';
        $payload['rincian'][0]['posisi'] = 'Operator Produksi';
        $payload['rincian'][1]['posisi'] = '  OPERATOR   produksi  ';
        try {
            $this->validate($payload);
            $this->fail('Duplicate division and position were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('rincian.1.posisi', $exception->errors());
        }
    }

    public function test_migration_can_be_reversed(): void
    {
        $this->migration()->down();
        $this->assertFalse(Schema::hasColumn('permintaan_tenaga_kerja', 'rincian'));
        $this->assertTrue(Schema::hasColumn('permintaan_tenaga_kerja', 'posisi'));
    }

    public function test_legacy_mysql_default_is_repaired_and_sql_mode_restored_even_if_ddl_fails(): void
    {
        $original = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ONLY_FULL_GROUP_BY';
        $connection = Mockery::mock(\Illuminate\Database\Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $connection->shouldReceive('selectOne')->once()->with('SELECT @@SESSION.sql_mode AS sql_mode')->andReturn((object) ['sql_mode' => $original]);
        $connection->shouldReceive('statement')->once()->ordered()->with('SET SESSION sql_mode = ?', ['STRICT_TRANS_TABLES,ONLY_FULL_GROUP_BY'])->andReturnTrue();
        $connection->shouldReceive('selectOne')->once()->with("SHOW COLUMNS FROM `permintaan_tenaga_kerja` WHERE Field = 'updated_at'")->andReturn((object) ['Default' => '0000-00-00 00:00:00']);
        $connection->shouldReceive('statement')->once()->ordered()->with('ALTER TABLE `permintaan_tenaga_kerja` ALTER COLUMN `updated_at` SET DEFAULT CURRENT_TIMESTAMP')->andReturnTrue();
        $connection->shouldReceive('statement')->once()->ordered()->with('SET SESSION sql_mode = ?', [$original])->andReturnTrue();
        Schema::shouldReceive('table')->once()->with('permintaan_tenaga_kerja', Mockery::type('Closure'))->andThrow(new RuntimeException('DDL failed'));
        DB::shouldReceive('connection')->with(null)->andReturn($connection);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DDL failed');
        $this->migration()->up();
    }

    public function test_multi_position_onboarding_uses_matching_position_and_does_not_guess_division(): void
    {
        $ptk = new PermintaanTenagaKerja($this->build($this->validate($this->payload())));
        $ptk->setRelation('departemen', null);
        $method = new ReflectionMethod(OnboardingCandidateSyncService::class, 'workPayloadFromPtk');
        $service = new OnboardingCandidateSyncService();
        $matched = $method->invoke($service, $ptk, new Lowongan(['nama_lowongan' => 'Mekanik']));
        $this->assertSame('Mekanik', $matched['jabatan']);
        $this->assertSame(11, $matched['divisi_id']);
        $unknown = $method->invoke($service, $ptk, new Lowongan(['nama_lowongan' => 'Teknisi Senior']));
        $this->assertSame('Teknisi Senior', $unknown['jabatan']);
        $this->assertNull($unknown['divisi_id']);
    }

    public function test_form_restores_all_rows_after_validation_failure(): void
    {
        session()->flashInput(['rincian' => $this->payload()['rincian']]);
        request()->setLaravelSession(session()->driver());
        $html = view('admin.permintaan-tenaga-kerja._rincian')->render();
        $this->assertStringContainsString('name="rincian[1][posisi]"', $html);
        $this->assertStringContainsString('value="Mekanik"', $html);
        $this->assertStringContainsString('data-selected="11"', $html);
        $dom = new \DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $this->assertSame('Laki-laki', $xpath->evaluate('string(//select[@name="rincian[0][jenis_kelamin]"]/option[@selected]/@value)'));
        $this->assertSame('Perempuan', $xpath->evaluate('string(//select[@name="rincian[1][jenis_kelamin]"]/option[@selected]/@value)'));
        $this->assertSame('<p>Pengalaman mekanik</p>', $xpath->evaluate('string(//textarea[@name="rincian[1][kualifikasi_ptk]"])'));
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_07_000000_add_rincian_to_permintaan_tenaga_kerja.php');
    }

    private function validate(array $payload): array
    {
        return (new ReflectionMethod(PermintaanTenagaKerjaController::class, 'validatePermintaanTenagaKerja'))->invoke(new PermintaanTenagaKerjaController(), Request::create('/', 'POST', $payload));
    }

    private function build(array $data): array
    {
        return (new ReflectionMethod(PermintaanTenagaKerjaController::class, 'buildPayload'))->invoke(new PermintaanTenagaKerjaController(), $data);
    }

    private function payload(): array
    {
        return [
            'no_surat_permintaan' => 'PTK/2026/001', 'departemen' => 1,
            'tanggal_pengajuan' => '2026-10-07', 'tanggal_terima' => '2026-10-07',
            'status_ptk' => 'Menunggu',
            'rincian' => [
                ['divisi' => 10, 'posisi' => 'Operator', 'kualifikasi_ptk' => '<p>Pengalaman <strong>operator</strong></p>', 'jenis_kelamin' => 'Laki-laki', 'jumlah_ptk' => 2, 'rentang_usia' => '18-35', 'background_pendidikan' => 'SMA/SMK'],
                ['divisi' => 11, 'posisi' => 'Mekanik', 'kualifikasi_ptk' => '<p>Pengalaman mekanik</p>', 'jenis_kelamin' => 'Perempuan', 'jumlah_ptk' => 5, 'rentang_usia' => '20-40', 'background_pendidikan' => 'D3'],
            ],
        ];
    }
}
