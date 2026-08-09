<?php

namespace Tests\Unit;

use App\Http\Controllers\BiodataController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class BiodataStructuredProfileValidationTest extends TestCase
{
    public function test_profile_accepts_multiple_hobbies_talents_and_achievements()
    {
        $validated = $this->validateProfile($this->validPayload());

        $this->assertCount(2, $validated['minat_bakat']['hobi']['olahraga']);
        $this->assertSame('Fotografi', $validated['minat_bakat']['hobi']['seni'][0]);
        $this->assertCount(2, $validated['prestasi_data']);
        $this->assertCount(2, $validated['pengalaman_kerja']);
    }

    public function test_profile_requires_at_least_one_talent()
    {
        $payload = $this->validPayload();
        $payload['minat_bakat']['bakat'] = [
            'olahraga' => [''],
            'seni' => [''],
            'lainnya' => [''],
        ];

        try {
            $this->validateProfile($payload);
            $this->fail('Validasi seharusnya menolak bakat kosong.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'minat_bakat.bakat.olahraga.0',
                $exception->errors()
            );
        }
    }

    public function test_profile_rejects_duplicate_value_in_the_same_category()
    {
        $payload = $this->validPayload();
        $payload['minat_bakat']['hobi']['olahraga'] = ['Futsal', 'futsal'];

        try {
            $this->validateProfile($payload);
            $this->fail('Validasi seharusnya menolak isian duplikat.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'minat_bakat.hobi.olahraga.0',
                $exception->errors()
            );
        }
    }

    public function test_profile_rejects_future_achievement_period()
    {
        $payload = $this->validPayload();
        $payload['prestasi_data'][0]['periode'] = now()->addMonth()->format('Y-m');

        try {
            $this->validateProfile($payload);
            $this->fail('Validasi seharusnya menolak periode prestasi di masa depan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('prestasi_data.0.periode', $exception->errors());
        }
    }

    public function test_profile_rejects_more_than_three_work_experiences()
    {
        $payload = $this->validPayload();
        $payload['pengalaman_kerja'] = array_fill(0, 4, $payload['pengalaman_kerja'][0]);

        try {
            $this->validateProfile($payload);
            $this->fail('Validasi seharusnya menolak lebih dari tiga pengalaman kerja.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pengalaman_kerja', $exception->errors());
        }
    }

    public function test_profile_rejects_work_end_period_before_start_period()
    {
        $payload = $this->validPayload();
        $payload['pengalaman_kerja'][0]['tanggal_mulai'] = '2025-06';
        $payload['pengalaman_kerja'][0]['tanggal_selesai'] = '2025-05';

        try {
            $this->validateProfile($payload);
            $this->fail('Validasi seharusnya menolak periode kerja yang terbalik.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pengalaman_kerja.0.tanggal_selesai', $exception->errors());
        }
    }

    public function test_current_work_experience_does_not_require_end_period()
    {
        $payload = $this->validPayload();
        $payload['pengalaman_kerja'][0]['masih_bekerja'] = '1';
        unset($payload['pengalaman_kerja'][0]['tanggal_selesai']);

        $validated = $this->validateProfile($payload);

        $this->assertSame('1', $validated['pengalaman_kerja'][0]['masih_bekerja']);
    }

    public function test_work_experiences_are_normalized_from_the_latest_period()
    {
        $payload = $this->validPayload();
        $rows = $this->normalizeWorkExperiences($payload);

        $this->assertSame('PT Industri Baru', $rows[0]['nama_perusahaan']);
        $this->assertSame(1, $rows[0]['urutan']);
        $this->assertSame('PT Industri Lama', $rows[1]['nama_perusahaan']);
        $this->assertSame(2, $rows[1]['urutan']);
    }

    private function validateProfile(array $payload): array
    {
        $method = new ReflectionMethod(BiodataController::class, 'validateProfile');
        $method->setAccessible(true);

        return $method->invoke(
            app(BiodataController::class),
            Request::create('/biodata/profile', 'POST', $payload)
        );
    }

    private function normalizeWorkExperiences(array $payload): array
    {
        $method = new ReflectionMethod(BiodataController::class, 'normalizedPengalamanKerjaRows');
        $method->setAccessible(true);

        return $method->invoke(
            app(BiodataController::class),
            Request::create('/biodata/profile', 'POST', $payload)
        );
    }

    private function validPayload(): array
    {
        return [
            'no_telp' => '081234567890',
            'no_kk' => '7401010101900002',
            'no_npwp' => '1234567890123456',
            'jenis_kelamin' => 'M',
            'tempat_lahir' => 'Kendari',
            'tanggal_lahir' => '1990-01-01',
            'agama' => 'ISLAM',
            'vaksin' => 'VAKSIN 3',
            'provinsi' => 1,
            'kabupaten' => 1,
            'kecamatan' => 1,
            'kelurahan' => 1,
            'alamat' => 'Alamat lengkap',
            'kode_pos' => '93231',
            'rt' => '001',
            'rw' => '002',
            'golongan_darah' => 'O',
            'tinggi_badan' => 170,
            'berat_badan' => 65,
            'pendidikan_terakhir' => 'SMA',
            'nama_instansi' => 'SMA Negeri',
            'jurusan' => 'IPA',
            'nilai_ipk' => '85',
            'tahun_lulus' => '2010-01-01',
            'nama_ayah' => 'Ayah',
            'nama_ibu' => 'Ibu',
            'status_pernikahan' => 'Belum Kawin',
            'nama_kontak_darurat' => 'Kontak Darurat',
            'no_telp_darurat' => '081234567891',
            'status_hubungan' => 'Saudara',
            'minat_bakat' => [
                'hobi' => [
                    'olahraga' => ['Futsal', 'Badminton'],
                    'seni' => ['Fotografi'],
                    'lainnya' => [''],
                ],
                'bakat' => [
                    'olahraga' => ['Renang'],
                    'seni' => ['Melukis'],
                    'lainnya' => ['Public Speaking'],
                ],
            ],
            'prestasi_data' => [
                [
                    'bidang' => 'Olahraga',
                    'bidang_lainnya' => '',
                    'jenis_prestasi' => 'Kejuaraan Futsal',
                    'peringkat' => 'Juara 1',
                    'tingkat' => 'Provinsi',
                    'periode' => '2025-05',
                ],
                [
                    'bidang' => 'Lainnya',
                    'bidang_lainnya' => 'Kepemimpinan',
                    'jenis_prestasi' => 'Pemuda Pelopor',
                    'peringkat' => 'Finalis',
                    'tingkat' => 'Nasional',
                    'periode' => '2024-08',
                ],
            ],
            'pengalaman_kerja' => [
                [
                    'nama_perusahaan' => 'PT Industri Lama',
                    'posisi' => 'Operator',
                    'tanggal_mulai' => '2023-01',
                    'tanggal_selesai' => '2024-05',
                ],
                [
                    'nama_perusahaan' => 'PT Industri Baru',
                    'posisi' => 'Senior Operator',
                    'tanggal_mulai' => '2024-06',
                    'tanggal_selesai' => '2025-05',
                ],
            ],
        ];
    }
}
