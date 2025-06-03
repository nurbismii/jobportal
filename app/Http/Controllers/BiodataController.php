<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Hris\Provinsi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\File;

class BiodataController extends Controller
{
    public function index()
    {
        $provinsis = Provinsi::all();
        $biodata = Biodata::with('getProvinsi', 'getKabupaten', 'getKecamatan', 'getKelurahan')->where('user_id', auth()->id())->first();

        return view('user.biodata.index', compact('provinsis', 'biodata'));
    }

    public function store(Request $request)
    {
        $biodata = Biodata::where('user_id', auth()->id())->first();

        $dokumenFields = [
            'cv',
            'pas_foto',
            'surat_lamaran',
            'ijazah',
            'ktp',
            'sim_b_2',
            'skck',
            'sertifikat_vaksin',
            'kartu_keluarga',
            'npwp',
            'ak1',
            'sertifikat_pendukung'
        ];

        $fileNames = [];

        $folderPath = public_path(Auth::user()->no_ktp . '/dokumen');

        // Buat folder jika belum ada
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        foreach ($dokumenFields as $field) {
            if ($request->hasFile($field)) {
                // Hapus file lama jika ada
                if ($biodata && $biodata->{$field}) {
                    $oldFilePath = $folderPath . '/' . $biodata->{$field};
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                // Simpan file baru
                $file = $request->file($field);
                $fileName = $file->getClientOriginalName();
                $file->move($folderPath, $fileName);
                $fileNames[$field] = $fileName;
            } else {
                // Gunakan data lama jika tidak ada file baru
                $fileNames[$field] = $biodata ? $biodata->{$field} : null;
            }
        }

        Biodata::updateOrCreate(
            [
                'user_id' => auth()->id()
            ],
            [
                // Biodata Pribadi
                'user_id' => auth()->id(),
                'no_ktp' => $request->no_ktp,
                'no_telp' => $request->no_telp,
                'no_kk' => $request->no_kk,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tempat_lahir' => $request->tempat_lahir,
                'tanggal_lahir' => $request->tanggal_lahir,
                'provinsi' => $request->provinsi,
                'kabupaten' => $request->kabupaten,
                'kecamatan' => $request->kecamatan,
                'kelurahan' => $request->kelurahan,
                'alamat' => $request->alamat,
                'kode_pos' => $request->kode_pos,
                'rt' => $request->rt,
                'rw' => $request->rw,
                'hobi' => $request->hobi,
                'golongan_darah' => $request->golongan_darah,
                'tinggi_badan' => $request->tinggi_badan,
                'berat_badan' => $request->berat_badan,

                // Pendidikan
                'pendidikan_terakhir' => $request->pendidikan_terakhir,
                'nama_instansi' => $request->nama_instansi,
                'jurusan' => $request->jurusan,
                'nilai_ipk' => $request->nilai_ipk,
                'tahun_masuk' => $request->tahun_masuk,
                'tahun_lulus' => $request->tahun_lulus,
                'prestasi' => $request->prestasi,

                // Keluarga
                'nama_ayah' => $request->nama_ayah,
                'nama_ibu' => $request->nama_ibu,
                'status_pernikahan' => $request->status_pernikahan,
                'tanggal_nikah' => $request->tanggal_nikah,
                'nama_pasangan' => $request->nama_pasangan,
                'jumlah_anak' => $request->jumlah_anak,
                'nama_anak_1' => $request->nama_anak_1,
                'nama_anak_2' => $request->nama_anak_2,
                'nama_anak_3' => $request->nama_anak_3,

                // Kontak darurat
                'nama_kontak_darurat' => $request->nama_kontak_darurat,
                'no_telepon_darurat' => $request->no_telp_darurat,
                'status_hubungan' => $request->status_hubungan,

                // Dokumen (diambil dari array fileNames)
                'cv' => $fileNames['cv'],
                'pas_foto' => $fileNames['pas_foto'],
                'surat_lamaran' => $fileNames['surat_lamaran'],
                'ijazah' => $fileNames['ijazah'],
                'ktp' => $fileNames['ktp'],
                'sim_b_2' => $fileNames['sim_b_2'],
                'skck' => $fileNames['skck'],
                'sertifikat_vaksin' => $fileNames['sertifikat_vaksin'],
                'kartu_keluarga' => $fileNames['kartu_keluarga'],
                'npwp' => $fileNames['npwp'],
                'ak1' => $fileNames['ak1'],
                'sertifikat_pendukung' => $fileNames['sertifikat_pendukung'],

                // Tambahan
                'status_pernyataan' => $request->pernyataan_1 . ', ' . $request->pernyataan_2,
            ]
        );

        Alert::success('success', 'Biodata diri berhasil ditambahkan.');
        return redirect()->back();
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

            $biodata->{$field} = null;
            $biodata->save();
        }

        Alert::success('Berhasil', ucfirst(str_replace('_', ' ', $field)) . ' berhasil dihapus.');
        return redirect()->back();
    }
}
