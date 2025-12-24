<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Hris\Provinsi;
use App\Models\SyaratKetentuan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class BiodataController extends Controller
{
    public function index()
    {
        if (!Auth::user()) {
            Alert::warning('Peringatan', 'Silahkan login terlebih dahulu.');
            return redirect()->route('login');
        }

        $provinsis = Provinsi::all();
        $biodata = Biodata::with('getProvinsi', 'getKabupaten', 'getKecamatan', 'getKelurahan')->where('user_id', auth()->id())->first();

        return view('user.biodata.index', compact('provinsis', 'biodata'));
    }

    public function store(Request $request)
    {
        try {
            $biodata = Biodata::where('user_id', auth()->id())->first();
            $syarat_ketentuan = SyaratKetentuan::where('id', 1)->first();

            $dokumenFields = [
                'cv' => 'CV',
                'pas_foto' => 'Pas Foto',
                'surat_lamaran' => 'Surat Lamaran',
                'ijazah' => 'Ijazah',
                'ktp' => 'KTP',
                'sim_b_2' => 'SIM B II Umum',
                'sio' => 'Surat Izin Operator',
                'skck' => 'SKCK',
                'sertifikat_vaksin' => 'Sertifikat Vaksin',
                'kartu_keluarga' => 'Kartu Keluarga',
                'npwp' => 'NPWP',
                'ak1' => 'Kartu AK1',
                'sertifikat_pendukung' => 'Sertifikat Pendukung'
            ];

            $fileNames = [];

            $fileNames = interventionImg($dokumenFields, $biodata, $request);

            $fileNames = $fileNames['files'];
            $oldFiles  = $fileNames['oldFiles'] ?? [];

            $biodata = Biodata::updateOrCreate(
                [
                    'user_id' => auth()->id()
                ],
                [
                    // Biodata Pribadi
                    'user_id' => auth()->id(),
                    'no_ktp' => $request->no_ktp,
                    'no_telp' => $request->no_telp,
                    'no_kk' => $request->no_kk,
                    'no_npwp' => $request->no_npwp,
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'tempat_lahir' => $request->tempat_lahir,
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'agama' => $request->agama,
                    'vaksin' => $request->vaksin,
                    'provinsi' => $request->provinsi,
                    'kabupaten' => $request->kabupaten,
                    'kecamatan' => $request->kecamatan,
                    'kelurahan' => $request->kelurahan,
                    'alamat' => $request->alamat,
                    'alamat_domisili' => $request->alamat_domisili,
                    'kode_pos' => $request->kode_pos,
                    'rt' => $request->rt,
                    'rw' => $request->rw,
                    'hobi' => $request->hobi,
                    'golongan_darah' => $request->golongan_darah,
                    'tinggi_badan' => $request->tinggi_badan,
                    'berat_badan' => $request->berat_badan,

                    // Pendidikan
                    'pendidikan_terakhir' => $request->pendidikan_terakhir,
                    'nama_instansi' => ucwords($request->nama_instansi),
                    'jurusan' => ucwords($request->jurusan),
                    'nilai_ipk' => $request->nilai_ipk,
                    'tahun_masuk' => $request->tahun_masuk,
                    'tahun_lulus' => $request->tahun_lulus,
                    'prestasi' => $request->prestasi,

                    // Keluarga
                    'nama_ayah' => ucwords($request->nama_ayah),
                    'nama_ibu' => ucwords($request->nama_ibu),
                    'status_pernikahan' => $request->status_pernikahan,
                    'tanggal_nikah' => $request->tanggal_nikah,
                    'nama_pasangan' => ucwords($request->nama_pasangan),
                    'jumlah_anak' => $request->jumlah_anak,
                    'nama_anak_1' => $request->nama_anak_1,
                    'nama_anak_2' => $request->nama_anak_2,
                    'nama_anak_3' => $request->nama_anak_3,

                    // Kontak darurat
                    'nama_kontak_darurat' => ucwords($request->nama_kontak_darurat),
                    'no_telepon_darurat' => $request->no_telp_darurat,
                    'status_hubungan' => $request->status_hubungan,

                    // Dokumen (diambil dari array fileNames)
                    'cv' => $fileNames['cv'],
                    'pas_foto' => $fileNames['pas_foto'],
                    'surat_lamaran' => $fileNames['surat_lamaran'],
                    'ijazah' => $fileNames['ijazah'],
                    'ktp' => $fileNames['ktp'],
                    'sim_b_2' => $fileNames['sim_b_2'],
                    'sio' => $fileNames['sio'],
                    'skck' => $fileNames['skck'],
                    'sertifikat_vaksin' => $fileNames['sertifikat_vaksin'],
                    'kartu_keluarga' => $fileNames['kartu_keluarga'],
                    'npwp' => $fileNames['npwp'],
                    'ak1' => $fileNames['ak1'],
                    'sertifikat_pendukung' => $fileNames['sertifikat_pendukung'],

                    // Tambahan
                    'status_pernyataan' => $syarat_ketentuan->syarat_ketentuan
                ]
            );

            foreach ($oldFiles as $oldFile) {
                $path = public_path(auth()->user()->no_ktp . '/dokumen/' . $oldFile);
                if (is_file($path)) {
                    unlink($path);
                }
            }

            // Check if SIM B II file is available before processing OCR
            if (isset($fileNames['sim_b_2']) && $fileNames['sim_b_2']) {
                // Langsung proses OCR saat file diunggah
                extractSimB2OnlyOCR($biodata);
            }

            Alert::success('success', 'Biodata sudah diubah. Silakan pilih lowongan dan kirim lamaran');
            return redirect()->to(route('lowongan-kerja.index'));
        } catch (\Exception $e) {
            Alert::error('Error', 'Terjadi kesalahan, coba beberapa saat lagi');
            Log::info('BiodataController Store Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function deleteFile($field)
    {
        $allowedFields = [
            'cv',
            'pas_foto',
            'surat_lamaran',
            'ijazah',
            'ktp',
            'sim_b_2',
            'sio',
            'skck',
            'sertifikat_vaksin',
            'kartu_keluarga',
            'npwp',
            'ak1',
            'sertifikat_pendukung'
        ];

        if (!in_array($field, $allowedFields)) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $biodata = Biodata::where('user_id', auth()->id())->firstOrFail();

        $fileName = $biodata->{$field};

        if ($fileName) {
            $filePath = public_path(Auth::user()->no_ktp . '/dokumen/' . $fileName);

            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            // Reset OCR data if SIM KTP Umum is deleted
            if ($field === 'ktp') {
                $biodata->ocr_ktp = null;
            }

            // Reset OCR data if SIM B II Umum is deleted
            if ($field === 'sim_b_2') {
                $biodata->ocr_sim_b2 = null;
                $biodata->parsed_sim_b2 = null;
            }

            $biodata->{$field} = null;
            $biodata->save();
        }

        Alert::success('Berhasil', ucfirst(str_replace('_', ' ', $field)) . ' berhasil dihapus.');
        return redirect()->to(route('biodata.index') . '#step5');
    }
}
