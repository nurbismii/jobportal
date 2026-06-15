<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BiodataStepProgressTest extends TestCase
{
    public function test_complete_documents_without_terms_approval_stops_at_step_five()
    {
        $biodata = (object) array_merge($this->completeBiodataFields(), [
            'status_pernyataan' => null,
        ]);

        $this->assertSame(5, calcutaionStep($biodata));
    }

    public function test_complete_documents_with_terms_approval_reaches_step_six()
    {
        $biodata = (object) array_merge($this->completeBiodataFields(), [
            'status_pernyataan' => '<p>Disetujui</p>',
        ]);

        $this->assertSame(6, calcutaionStep($biodata));
    }

    private function completeBiodataFields(): array
    {
        return [
            'no_ktp' => '7401010101900001',
            'no_telp' => '081234567890',
            'no_kk' => '7401010101900002',
            'no_npwp' => '12.345.678.9-012.345',
            'jenis_kelamin' => 'M',
            'agama' => 'ISLAM',
            'vaksin' => 'VAKSIN 3',
            'tempat_lahir' => 'Kendari',
            'tanggal_lahir' => '1990-01-01',
            'provinsi' => 1,
            'kabupaten' => 1,
            'kecamatan' => 1,
            'kelurahan' => 1,
            'alamat' => 'Alamat lengkap',
            'kode_pos' => '93231',
            'rt' => '001',
            'rw' => '002',
            'hobi' => 'Membaca',
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
            'no_telepon_darurat' => '081234567891',
            'status_hubungan' => 'Saudara',
            'cv' => 'cv.pdf',
            'pas_foto' => 'pas-foto.jpg',
            'surat_lamaran' => 'surat-lamaran.pdf',
            'ijazah' => 'ijazah.pdf',
            'ktp' => 'ktp.jpg',
            'skck' => 'skck.pdf',
            'sertifikat_vaksin' => 'vaksin.pdf',
            'kartu_keluarga' => 'kk.pdf',
            'npwp' => 'npwp.pdf',
            'ak1' => 'ak1.pdf',
        ];
    }
}
