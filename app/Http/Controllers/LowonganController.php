<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lowongan;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class LowonganController extends Controller
{
    public function index()
    {
        $lowongans = Lowongan::orderBy('id', 'desc')->get();

        return view('user.lowongan-kerja.index', compact('lowongans'));
    }

    public function show($id)
    {
        $biodata = Biodata::where('user_id', auth()->id())->first();

        if (!$biodata) {
            Alert::info('Informasi', 'Silahkan login terlebih dahulu untuk melihat lowongan kerja.');
            return redirect()->route('login');
        }

        // Daftar field dan label ramah pengguna
        $fieldLabels = [

            // Identitas Pribadi
            'user_id' => 'ID Pengguna',
            'no_ktp' => 'Nomor KTP',
            'no_telp' => 'Nomor Telepon',
            'no_kk' => 'Nomor Kartu Keluarga',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',

            // Alamat Domisili
            'provinsi' => 'Provinsi',
            'kabupaten' => 'Kabupaten/Kota',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan/Desa',
            'alamat' => 'Alamat Lengkap',
            'kode_pos' => 'Kode Pos',
            'rt' => 'RT',
            'rw' => 'RW',

            // Informasi Pribadi Tambahan
            'hobi' => 'Hobi',
            'golongan_darah' => 'Golongan Darah',
            'tinggi_badan' => 'Tinggi Badan (cm)',
            'berat_badan' => 'Berat Badan (kg)',

            // Riwayat Pendidikan
            'pendidikan_terakhir' => 'Pendidikan Terakhir',
            'nama_instansi' => 'Nama Instansi Pendidikan',
            'jurusan' => 'Jurusan',
            'nilai_ipk' => 'Nilai IPK/NEM',
            'tahun_masuk' => 'Tahun Masuk',
            'tahun_lulus' => 'Tahun Lulus',

            // Data Keluarga
            'nama_ayah' => 'Nama Ayah',
            'nama_ibu' => 'Nama Ibu',
            'status_pernikahan' => 'Status Pernikahan',

            // Kontak Darurat
            'nama_kontak_darurat' => 'Nama Kontak Darurat',
            'no_telepon_darurat' => 'Nomor Telepon Darurat',
            'status_hubungan' => 'Hubungan dengan Kontak Darurat',

            // Dokumen Wajib
            'cv' => 'Curriculum Vitae (CV)',
            'pas_foto' => 'Pas Foto',
            'surat_lamaran' => 'Surat Lamaran',
            'ijazah' => 'Ijazah',
            'ktp' => 'KTP (Kartu Tanda Penduduk)',
            'sim_b_2' => 'SIM B2',
            'skck' => 'SKCK (Surat Keterangan Catatan Kepolisian)',
            'sertifikat_vaksin' => 'Sertifikat Vaksin',
            'kartu_keluarga' => 'Kartu Keluarga (KK)',
            'npwp' => 'NPWP (Nomor Pokok Wajib Pajak)',
            'ak1' => 'Kartu AK1 (Kartu Pencari Kerja)',
        ];

        // Cek field yang kosong
        $emptyFields = [];
        foreach ($fieldLabels as $field => $label) {
            if (empty($biodata->$field)) {
                $emptyFields[] = $label;
            }
        }

        // Jika ada field kosong, kembalikan dengan alert
        if (count($emptyFields)) {
            $fieldList = implode(', ', $emptyFields);
            return view('user.lowongan-kerja.verifikasi',  [
                'emptyFields' => $emptyFields
            ])->with('error', 'Silakan lengkapi data berikut sebelum melamar');
        }


        $lowongan = Lowongan::findOrFail($id);

        return view('user.lowongan-kerja.show', compact('lowongan'));
    }
}
