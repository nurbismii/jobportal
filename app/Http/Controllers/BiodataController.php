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
    private function biodataIdentityDefaults(): array
    {
        return [
            'user_id' => auth()->id(),
            'no_ktp' => auth()->user()->no_ktp,
        ];
    }

    private function accountDataLockedMessage(): string
    {
        return 'Biodata dan dokumen tidak dapat diubah karena akun Anda tercatat aktif bekerja.';
    }

    private function step1to4ValidationRules(): array
    {
        return [
            'no_telp' => 'required|digits_between:11,13',
            'no_kk' => 'required|digits:16',
            'no_npwp' => 'required|string|max:20',
            'jenis_kelamin' => 'required|string',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'agama' => 'required|string',
            'vaksin' => 'required|string',
            'provinsi' => 'required|numeric',
            'kabupaten' => 'required|numeric',
            'kecamatan' => 'required|numeric',
            'kelurahan' => 'required|numeric',
            'alamat' => 'required|string',
            'kode_pos' => 'required|digits:5',
            'rt' => 'required|string|max:3',
            'rw' => 'required|string|max:3',
            'hobi' => 'required|string|max:255',
            'golongan_darah' => 'required|string',
            'tinggi_badan' => 'required|numeric|min:0',
            'berat_badan' => 'required|numeric|min:0',
            'pendidikan_terakhir' => 'required|string',
            'nama_instansi' => 'required|string|max:255',
            'jurusan' => 'required|string|max:255',
            'nilai_ipk' => 'required|string|max:50',
            'tahun_lulus' => 'required|date',
            'nama_ayah' => 'required|string|max:255',
            'nama_ibu' => 'required|string|max:255',
            'status_pernikahan' => 'required|string',
            'tanggal_nikah' => 'nullable|date|required_if:status_pernikahan,Kawin',
            'nama_pasangan' => 'nullable|string|max:255|required_if:status_pernikahan,Kawin',
            'nama_kontak_darurat' => 'required|string|max:255',
            'no_telp_darurat' => 'required|digits_between:11,13',
            'status_hubungan' => 'required|string',
        ];
    }

    private function step1to4ValidationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_if' => ':attribute wajib diisi saat :other adalah :value.',
            'string' => ':attribute tidak valid.',
            'numeric' => ':attribute harus berupa angka.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'digits' => ':attribute harus terdiri dari :digits digit.',
            'digits_between' => ':attribute harus terdiri dari :min sampai :max digit.',
            'max.string' => ':attribute maksimal :max karakter.',
            'max.numeric' => ':attribute maksimal :max.',
            'min.numeric' => ':attribute minimal :min.',
        ];
    }

    private function step1to4ValidationAttributes(): array
    {
        return [
            'no_telp' => 'No Telp',
            'no_kk' => 'No Kartu Keluarga',
            'no_npwp' => 'NPWP',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'agama' => 'Agama',
            'vaksin' => 'Vaksin',
            'provinsi' => 'Provinsi',
            'kabupaten' => 'Kabupaten/Kota',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan/Desa',
            'alamat' => 'Alamat Lengkap',
            'kode_pos' => 'Kode Pos',
            'rt' => 'RT',
            'rw' => 'RW',
            'hobi' => 'Hobi',
            'golongan_darah' => 'Golongan Darah',
            'tinggi_badan' => 'Tinggi Badan',
            'berat_badan' => 'Berat Badan',
            'pendidikan_terakhir' => 'Pendidikan Terakhir',
            'nama_instansi' => 'Nama Sekolah / Kampus',
            'jurusan' => 'Jurusan',
            'nilai_ipk' => 'Nilai Akhir / IPK',
            'tahun_lulus' => 'Tahun Lulus',
            'nama_ayah' => 'Nama Ayah',
            'nama_ibu' => 'Nama Ibu',
            'status_pernikahan' => 'Status Pernikahan',
            'tanggal_nikah' => 'Tanggal Pernikahan',
            'nama_pasangan' => 'Nama Suami / Istri',
            'nama_kontak_darurat' => 'Nama Kontak Darurat',
            'no_telp_darurat' => 'No Telepon Darurat',
            'status_hubungan' => 'Status Hubungan',
        ];
    }

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
        if (auth()->user()->hasActiveEmploymentStatusLock()) {
            Alert::warning('Peringatan', $this->accountDataLockedMessage());
            return redirect()->to(route('biodata.index') . '#step1');
        }

        try {
            $biodata = Biodata::where('user_id', auth()->id())->first();
            $syarat_ketentuan = SyaratKetentuan::where('id', 1)->first();

            $dokumenFields = [
                'sim_b_2' => 'SIM B II Umum'
            ];

            $fileNames = [];

            $fileNames = interventionImg($dokumenFields, $biodata, $request);

            $fileNames = $fileNames['files'];
            $oldFiles  = $fileNames['oldFiles'] ?? [];


            Biodata::updateOrCreate(
                [
                    'user_id' => auth()->id()
                ],
                array_merge($this->biodataIdentityDefaults(), [
                    'status_pernyataan' => $syarat_ketentuan->syarat_ketentuan
                ])
            );

            $biodata = Biodata::where('user_id', auth()->id())->first();

            foreach ($oldFiles as $oldFile) {
                $path = public_path(auth()->user()->no_ktp . '/dokumen/' . $oldFile);
                if (is_file($path)) {
                    unlink($path);
                }
            }

            // Check if SIM B II file is available before processing OCR
            if ($biodata && isset($fileNames['sim_b_2']) && $fileNames['sim_b_2'] && $biodata->ocr_sim_b2 == null) {
                // Langsung proses OCR saat file diunggah
                extractSimB2OnlyOCR($biodata);
            }

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
        if (auth()->user()->hasActiveEmploymentStatusLock()) {
            return response()->json([
                'status' => false,
                'message' => $this->accountDataLockedMessage(),
            ], 403);
        }

        $validatedData = $request->validate(
            $this->step1to4ValidationRules(),
            $this->step1to4ValidationMessages(),
            $this->step1to4ValidationAttributes()
        );

        Biodata::updateOrCreate(
            [
                'user_id' => auth()->id()
            ],
            array_merge($this->biodataIdentityDefaults(), [
                // Biodata Pribadi
                'no_ktp' => $request->no_ktp ?: auth()->user()->no_ktp,
                'no_telp' => $validatedData['no_telp'],
                'no_kk' => $validatedData['no_kk'],
                'no_npwp' => $validatedData['no_npwp'],
                'jenis_kelamin' => $validatedData['jenis_kelamin'],
                'tempat_lahir' => $validatedData['tempat_lahir'],
                'tanggal_lahir' => $validatedData['tanggal_lahir'],
                'agama' => $validatedData['agama'],
                'vaksin' => $validatedData['vaksin'],
                'provinsi' => $validatedData['provinsi'],
                'kabupaten' => $validatedData['kabupaten'],
                'kecamatan' => $validatedData['kecamatan'],
                'kelurahan' => $validatedData['kelurahan'],
                'alamat' => $validatedData['alamat'],
                'alamat_domisili' => $request->alamat_domisili,
                'kode_pos' => $validatedData['kode_pos'],
                'rt' => $validatedData['rt'],
                'rw' => $validatedData['rw'],
                'hobi' => $validatedData['hobi'],
                'golongan_darah' => $validatedData['golongan_darah'],
                'tinggi_badan' => $validatedData['tinggi_badan'],
                'berat_badan' => $validatedData['berat_badan'],

                // Pendidikan
                'pendidikan_terakhir' => $validatedData['pendidikan_terakhir'],
                'nama_instansi' => ucwords($validatedData['nama_instansi']),
                'jurusan' => ucwords($validatedData['jurusan']),
                'nilai_ipk' => $validatedData['nilai_ipk'],
                'tahun_masuk' => $request->tahun_masuk,
                'tahun_lulus' => $validatedData['tahun_lulus'],
                'prestasi' => $request->prestasi,

                // Keluarga
                'nama_ayah' => ucwords($validatedData['nama_ayah']),
                'nama_ibu' => ucwords($validatedData['nama_ibu']),
                'status_pernikahan' => $validatedData['status_pernikahan'],
                'tanggal_nikah' => $validatedData['tanggal_nikah'] ?? null,
                'nama_pasangan' => !empty($validatedData['nama_pasangan']) ? ucwords($validatedData['nama_pasangan']) : null,
                'jumlah_anak' => $request->jumlah_anak,
                'nama_anak_1' => $request->nama_anak_1,
                'nama_anak_2' => $request->nama_anak_2,
                'nama_anak_3' => $request->nama_anak_3,

                // Kontak darurat
                'nama_kontak_darurat' => ucwords($validatedData['nama_kontak_darurat']),
                'no_telepon_darurat' => $validatedData['no_telp_darurat'],
                'status_hubungan' => $validatedData['status_hubungan'],
            ])
        );

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil disimpan.',
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

        if (auth()->user()->hasActiveEmploymentStatusLock()) {
            $message = $this->accountDataLockedMessage();

            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            Alert::warning('Peringatan', $message);

            return redirect()->to(route('biodata.index') . '#step5');
        }

        $biodata = Biodata::where('user_id', auth()->id())->firstOrFail();

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
        if (auth()->user()->hasActiveEmploymentStatusLock()) {
            return response()->json([
                'success' => false,
                'message' => $this->accountDataLockedMessage(),
            ], 403);
        }

        try {
            $rules = [
                'cv' => 'required|mimes:pdf|max:2048',
                'pas_foto' => 'required|image|mimes:jpeg,jpg,png|max:2048',
                'surat_lamaran' => 'required|mimes:pdf|max:2048',
                'ijazah' => 'required|mimes:pdf|max:2048',
                'ktp' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'sim_b_2' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'skck' => 'required|mimes:pdf|max:2048',
                'sio' => 'required|image|mimes:jpeg,jpg,png|max:2048',
                'sertifikat_vaksin' => 'required|mimes:pdf|max:2048',
                'kartu_keluarga' => 'required|mimes:pdf|max:2048',
                'npwp' => 'required|mimes:pdf|max:2048',
                'ak1' => 'required|mimes:pdf|max:2048',
                'sertifikat_pendukung' => 'required|mimes:pdf|max:51200',
            ];

            $maxSizeMessages = [
                'cv.max' => 'Ukuran CV maksimal 2 MB.',
                'pas_foto.max' => 'Ukuran Pas Foto maksimal 2 MB.',
                'surat_lamaran.max' => 'Ukuran Surat Lamaran maksimal 2 MB.',
                'ijazah.max' => 'Ukuran Ijazah dan Transkrip nilai maksimal 2 MB.',
                'ktp.max' => 'Ukuran KTP maksimal 2 MB.',
                'sim_b_2.max' => 'Ukuran SIM B II maksimal 2 MB.',
                'skck.max' => 'Ukuran SKCK maksimal 2 MB.',
                'sio.max' => 'Ukuran SIO maksimal 2 MB.',
                'sertifikat_vaksin.max' => 'Ukuran Sertifikat Vaksin maksimal 2 MB.',
                'kartu_keluarga.max' => 'Ukuran Kartu Keluarga maksimal 2 MB.',
                'npwp.max' => 'Ukuran NPWP maksimal 2 MB.',
                'ak1.max' => 'Ukuran AK1 maksimal 2 MB.',
                'sertifikat_pendukung.max' => 'Ukuran Sertifikat Pendukung maksimal 50 MB.',
            ];

            $messages = [
                'required' => ':attribute wajib diupload',
                'mimes'    => 'Format :attribute harus berupa :values',
                'image'    => ':attribute harus berupa foto/gambar',
            ] + $maxSizeMessages;

            $attributes = [
                'cv' => 'CV',
                'pas_foto' => 'Pas Foto',
                'surat_lamaran' => 'Surat Lamaran',
                'ijazah' => 'Ijazah',
                'ktp' => 'KTP',
                'sim_b_2' => 'SIM B II',
                'sio' => 'SIO',
                'skck' => 'SKCK',
                'sertifikat_vaksin' => 'Sertifikat Vaksin',
                'kartu_keluarga' => 'Kartu Keluarga',
                'npwp' => 'NPWP',
                'ak1' => 'AK1',
                'sertifikat_pendukung' => 'Sertifikat Pendukung',
            ];

            $request->validate(
                array_intersect_key($rules, $request->files->all()),
                $messages,
                $attributes
            );

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
                array_merge($this->biodataIdentityDefaults(), [
                    $uploadedField => $fileName,
                ])
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
        } catch (\Illuminate\Validation\ValidationException $ve) {
            Log::info('Upload AJAX Validation Error: ' . json_encode($ve->errors()));
            return response()->json([
                'success' => false,
                'errors' => $ve->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::info('Upload AJAX Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Pastikan file sesuai ketentuan dan ukuran maksimal 2mb'
            ], 500);
        }
    }
}
