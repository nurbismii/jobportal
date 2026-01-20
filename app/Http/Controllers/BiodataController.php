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
            $syarat_ketentuan = SyaratKetentuan::where('id', 1)->first();

            Biodata::updateOrCreate(
                [
                    'user_id' => auth()->id()
                ],
                [
                    'status_pernyataan' => $syarat_ketentuan->syarat_ketentuan
                ]
            );

            Alert::success('success', 'Biodata sudah diperbarui, silakan pilih lowongan dan kirim lamaran');
            return redirect()->to(route('lowongan-kerja.index'));
        } catch (\Exception $e) {
            Alert::error('Error', 'Terjadi kesalahan, coba beberapa saat lagi');
            Log::info('BiodataController Store Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function storeStep1to4(Request $request)
    {
        $syarat_ketentuan = SyaratKetentuan::where('id', 1)->first();

        $validatedData = $request->validate(
            [
                'provinsi' => 'required|numeric',
                'kabupaten' => 'required|numeric',
                'kecamatan' => 'required|numeric',
                'kelurahan' => 'required|numeric',
            ],
            [
                'provinsi.required' => 'Provinsi wajib diisi.',
                'kabupaten.required' => 'Kabupaten/Kota wajib diisi.',
                'kecamatan.required' => 'Kecamatan wajib diisi.',
                'kelurahan.required' => 'Kelurahan wajib diisi.',

                'provinsi.numeric' => 'Provinsi tidak valid.',
                'kabupaten.numeric' => 'Kabupaten/Kota tidak valid.',
                'kecamatan.numeric' => 'Kecamatan tidak valid.',
                'kelurahan.numeric' => 'Kelurahan tidak valid.',
            ]
        );

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
                'no_npwp' => $request->no_npwp,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tempat_lahir' => $request->tempat_lahir,
                'tanggal_lahir' => $request->tanggal_lahir,
                'agama' => $request->agama,
                'vaksin' => $request->vaksin,
                'provinsi' => $validatedData['provinsi'],
                'kabupaten' => $validatedData['kabupaten'],
                'kecamatan' => $validatedData['kecamatan'],
                'kelurahan' => $validatedData['kelurahan'],
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

                // Tambahan
                'status_pernyataan' => $syarat_ketentuan->syarat_ketentuan
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Data kontak darurat tersimpan'
        ]);
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
            return request()->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Field tidak valid'], 403)
                : abort(403);
        }

        $biodata = Biodata::where('user_id', auth()->id())->firstOrFail();

        // 🔒 KTP terkunci OCR
        if ($field === 'ktp' && $biodata->isValidOcrKtp()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'KTP tidak dapat dihapus karena data OCR masih valid'
                ], 422);
            }

            Alert::error('Gagal', 'KTP tidak dapat dihapus karena data OCR masih valid.');
            return redirect()->to(route('biodata.index') . '#step5');
        }

        $fileName = $biodata->{$field};

        if ($fileName) {
            $filePath = public_path(Auth::user()->no_ktp . '/dokumen/' . $fileName);

            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            if ($field === 'ktp') {
                $biodata->ocr_ktp = null;
            }

            if ($field === 'sim_b_2') {
                $biodata->ocr_sim_b2 = null;
                $biodata->parsed_sim_b2 = null;
            }

            $biodata->{$field} = null;
            $biodata->save();
        }

        // RESPONSE AJAX
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'field' => $field
            ]);
        }

        return redirect()->to(route('biodata.index') . '#step5');
    }

    public function uploadDocument(Request $request)
    {
        try {
            $biodata = Biodata::where('user_id', auth()->id())->first();

            $dokumenFields = [
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

            // cari field yang dikirim
            $uploadedField = collect($dokumenFields)->first(fn($f) => $request->hasFile($f));

            if (!$uploadedField) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada file yang dikirim'
                ], 422);
            }

            // pakai helper lama
            $result = interventionImg([$uploadedField => $uploadedField], $biodata, $request);

            $fileName = $result['files'][$uploadedField] ?? null;
            $oldFiles = $result['oldFiles'] ?? [];

            Biodata::updateOrCreate(
                ['user_id' => auth()->id()],
                [$uploadedField => $fileName]
            );

            // hapus file lama
            foreach ($oldFiles as $old) {
                $path = public_path(auth()->user()->no_ktp . '/dokumen/' . $old);
                if (is_file($path)) unlink($path);
            }

            return response()->json([
                'success' => true,
                'field'   => $uploadedField,
                'file'    => $fileName,
                'path' => auth()->user()->no_ktp . '/dokumen/' . $fileName
            ]);
        } catch (\Exception $e) {
            Log::error('Upload AJAX Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload dokumen'
            ], 500);
        }
    }
}
