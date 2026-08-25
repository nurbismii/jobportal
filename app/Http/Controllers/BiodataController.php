<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\BiodataMinatBakat;
use App\Models\BiodataPrestasi;
use App\Models\Hris\Provinsi;
use App\Models\SyaratKetentuan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
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

    private function profileValidationRules(): array
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
            'golongan_darah' => 'required|string',
            'tinggi_badan' => 'required|numeric|min:0',
            'berat_badan' => 'required|numeric|min:0',
            'pendidikan_terakhir' => 'required|string',
            'nama_instansi' => 'required|string|max:255',
            'jurusan' => 'required|string|max:255',
            'nilai_ipk' => 'required|string|max:50',
            'tahun_lulus' => 'required|date',
            'pengalaman_kerja' => 'nullable|array|max:3',
            'pengalaman_kerja.*.nama_perusahaan' => 'required|string|max:150',
            'pengalaman_kerja.*.posisi' => 'required|string|max:150',
            'pengalaman_kerja.*.tanggal_mulai' => 'required|date_format:Y-m|before_or_equal:' . now()->format('Y-m'),
            'pengalaman_kerja.*.tanggal_selesai' => 'nullable|required_unless:pengalaman_kerja.*.masih_bekerja,1|date_format:Y-m|before_or_equal:' . now()->format('Y-m'),
            'pengalaman_kerja.*.masih_bekerja' => 'nullable|boolean',
            'nama_ayah' => 'required|string|max:255',
            'nama_ibu' => 'required|string|max:255',
            'status_pernikahan' => 'required|string',
            'tanggal_nikah' => 'nullable|date|required_if:status_pernikahan,Kawin',
            'nama_pasangan' => 'nullable|string|max:255|required_if:status_pernikahan,Kawin',
            'nama_kontak_darurat' => 'required|string|max:255',
            'no_telp_darurat' => 'required|digits_between:11,13',
            'status_hubungan' => 'required|string',
            'minat_bakat' => 'nullable|array',
            'minat_bakat.hobi' => 'nullable|array',
            'minat_bakat.bakat' => 'nullable|array',
            'minat_bakat.*.*' => 'nullable|array|max:10',
            'minat_bakat.*.*.*' => 'nullable|string|max:100',
            'prestasi_data' => 'nullable|array|max:10',
            'prestasi_data.*.bidang' => 'required|string|in:' . implode(',', BiodataPrestasi::FIELDS),
            'prestasi_data.*.bidang_lainnya' => 'nullable|string|max:100|required_if:prestasi_data.*.bidang,Lainnya',
            'prestasi_data.*.jenis_prestasi' => 'required|string|max:150',
            'prestasi_data.*.peringkat' => 'required|string|max:100',
            'prestasi_data.*.tingkat' => 'required|string|in:' . implode(',', BiodataPrestasi::LEVELS),
            'prestasi_data.*.periode' => 'required|date_format:Y-m|before_or_equal:' . now()->format('Y-m'),
        ];
    }

    private function profileValidationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_if' => ':attribute wajib diisi saat :other adalah :value.',
            'required_unless' => ':attribute wajib diisi.',
            'string' => ':attribute tidak valid.',
            'numeric' => ':attribute harus berupa angka.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'digits' => ':attribute harus terdiri dari :digits digit.',
            'digits_between' => ':attribute harus terdiri dari :min sampai :max digit.',
            'max.string' => ':attribute maksimal :max karakter.',
            'max.numeric' => ':attribute maksimal :max.',
            'max.array' => ':attribute maksimal :max data.',
            'min.numeric' => ':attribute minimal :min.',
            'array' => ':attribute tidak valid.',
            'in' => ':attribute tidak valid.',
            'date_format' => ':attribute harus menggunakan format bulan dan tahun.',
            'before_or_equal' => ':attribute tidak boleh melewati periode saat ini.',
        ];
    }

    private function profileValidationAttributes(): array
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
            'minat_bakat.hobi' => 'Hobi',
            'minat_bakat.bakat' => 'Bakat',
            'minat_bakat.*.*.*' => 'Isian minat atau bakat',
            'prestasi_data.*.bidang' => 'Bidang prestasi',
            'prestasi_data.*.bidang_lainnya' => 'Bidang lainnya',
            'prestasi_data.*.jenis_prestasi' => 'Nama/Jenis prestasi',
            'prestasi_data.*.peringkat' => 'Peringkat/Pencapaian',
            'prestasi_data.*.tingkat' => 'Tingkat prestasi',
            'prestasi_data.*.periode' => 'Periode prestasi',
            'pengalaman_kerja' => 'Pengalaman kerja',
            'pengalaman_kerja.*.nama_perusahaan' => 'Nama perusahaan',
            'pengalaman_kerja.*.posisi' => 'Posisi/Jabatan',
            'pengalaman_kerja.*.tanggal_mulai' => 'Periode mulai kerja',
            'pengalaman_kerja.*.tanggal_selesai' => 'Periode selesai kerja',
            'pengalaman_kerja.*.masih_bekerja' => 'Status masih bekerja',
        ];
    }

    private function completedProfileBiodataFields(): array
    {
        return [
            'no_telp',
            'no_kk',
            'no_npwp',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'agama',
            'vaksin',
            'provinsi',
            'kabupaten',
            'kecamatan',
            'kelurahan',
            'alamat',
            'kode_pos',
            'rt',
            'rw',
            'golongan_darah',
            'tinggi_badan',
            'berat_badan',
            'pendidikan_terakhir',
            'nama_instansi',
            'jurusan',
            'nilai_ipk',
            'tahun_lulus',
            'nama_ayah',
            'nama_ibu',
            'status_pernikahan',
            'nama_kontak_darurat',
            'no_telepon_darurat',
            'status_hubungan',
        ];
    }

    private function hasCompletedProfile(?Biodata $biodata): bool
    {
        if (! $biodata) {
            return false;
        }

        foreach ($this->completedProfileBiodataFields() as $field) {
            $value = $biodata->{$field};

            if (is_string($value)) {
                if (trim($value) === '') {
                    return false;
                }

                continue;
            }

            if ($value === null) {
                return false;
            }
        }

        if ($biodata->status_pernikahan === 'Kawin') {
            if (blank($biodata->tanggal_nikah) || blank($biodata->nama_pasangan)) {
                return false;
            }
        }

        return true;
    }

    private function incompleteProfileMessage(): string
    {
        return 'Lengkapi data profil pada langkah 1 sampai 6 terlebih dahulu sebelum melanjutkan ke dokumen.';
    }

    private function normalizeText($value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function normalizedMinatBakatRows(Request $request): array
    {
        $rows = [];

        foreach (BiodataMinatBakat::TYPES as $type) {
            foreach (BiodataMinatBakat::CATEGORIES as $category) {
                foreach ((array) $request->input("minat_bakat.{$type}.{$category}", []) as $name) {
                    $name = $this->normalizeText($name);

                    if ($name !== '') {
                        $rows[] = [
                            'tipe' => $type,
                            'kategori' => $category,
                            'nama' => $name,
                        ];
                    }
                }
            }
        }

        return $rows;
    }

    private function normalizedPrestasiRows(Request $request): array
    {
        return collect($request->input('prestasi_data', []))
            ->map(function ($row) {
                return [
                    'bidang' => $this->normalizeText($row['bidang'] ?? ''),
                    'bidang_lainnya' => $this->normalizeText($row['bidang_lainnya'] ?? '') ?: null,
                    'jenis_prestasi' => $this->normalizeText($row['jenis_prestasi'] ?? ''),
                    'peringkat' => $this->normalizeText($row['peringkat'] ?? ''),
                    'tingkat' => $this->normalizeText($row['tingkat'] ?? ''),
                    'periode' => $this->normalizeText($row['periode'] ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizedPengalamanKerjaRows(Request $request): array
    {
        return collect($request->input('pengalaman_kerja', []))
            ->map(function ($row) {
                $masihBekerja = filter_var(
                    $row['masih_bekerja'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );

                return [
                    'nama_perusahaan' => $this->normalizeText($row['nama_perusahaan'] ?? ''),
                    'posisi' => $this->normalizeText($row['posisi'] ?? ''),
                    'tanggal_mulai' => $this->normalizeText($row['tanggal_mulai'] ?? ''),
                    'tanggal_selesai' => $masihBekerja
                        ? null
                        : ($this->normalizeText($row['tanggal_selesai'] ?? '') ?: null),
                    'masih_bekerja' => $masihBekerja,
                ];
            })
            ->sortByDesc(function ($row) {
                $periodeTerbaru = $row['masih_bekerja']
                    ? '9999-12'
                    : ($row['tanggal_selesai'] ?? '0000-00');

                return $periodeTerbaru . '|' . $row['tanggal_mulai'];
            })
            ->values()
            ->map(function ($row, $index) {
                $row['urutan'] = $index + 1;

                return $row;
            })
            ->all();
    }

    private function validateProfile(Request $request): array
    {
        $validator = Validator::make(
            $request->all(),
            $this->profileValidationRules(),
            $this->profileValidationMessages(),
            $this->profileValidationAttributes()
        );

        $validator->after(function ($validator) use ($request) {
            $rows = $this->normalizedMinatBakatRows($request);

            foreach (BiodataMinatBakat::TYPES as $type) {
                $typeRows = collect($rows)->where('tipe', $type);

                $duplicates = $typeRows
                    ->groupBy(function ($row) {
                        return $row['kategori'] . '|' . mb_strtolower($row['nama']);
                    })
                    ->filter(function ($items) {
                        return $items->count() > 1;
                    });

                if ($duplicates->isNotEmpty()) {
                    $validator->errors()->add(
                        "minat_bakat.{$type}.olahraga.0",
                        ucfirst($type) . ' tidak boleh memiliki isian duplikat dalam kategori yang sama.'
                    );
                }
            }

            foreach ((array) $request->input('pengalaman_kerja', []) as $index => $row) {
                $tanggalMulai = $this->normalizeText($row['tanggal_mulai'] ?? '');
                $tanggalSelesai = $this->normalizeText($row['tanggal_selesai'] ?? '');
                $masihBekerja = filter_var(
                    $row['masih_bekerja'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );

                if (! $masihBekerja && $tanggalMulai !== '' && $tanggalSelesai !== '' && $tanggalSelesai < $tanggalMulai) {
                    $validator->errors()->add(
                        "pengalaman_kerja.{$index}.tanggal_selesai",
                        'Periode selesai kerja tidak boleh lebih awal dari periode mulai.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    private function currentSyaratKetentuan(): ?SyaratKetentuan
    {
        return SyaratKetentuan::query()
            ->whereNotNull('syarat_ketentuan')
            ->where('syarat_ketentuan', '<>', '')
            ->orderByDesc('id')
            ->first();
    }

    public function index()
    {
        if (!Auth::user()) {
            Alert::warning('Peringatan', 'Silahkan login terlebih dahulu.');
            return redirect()->route('login');
        }

        $provinsis = Provinsi::all();
        $biodata = Biodata::with(
            'getProvinsi',
            'getKabupaten',
            'getKecamatan',
            'getKelurahan',
            'minatBakat',
            'daftarPrestasi',
            'pengalamanKerja'
        )->where('user_id', auth()->id())->first();
        $syaratKetentuan = $this->currentSyaratKetentuan();

        return view('user.biodata.index', compact('provinsis', 'biodata', 'syaratKetentuan'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->hasActiveEmploymentStatusLock()) {
            Alert::warning('Peringatan', $this->accountDataLockedMessage());
            return redirect()->to(route('biodata.index') . '#step1');
        }

        try {
            $biodata = Biodata::where('user_id', auth()->id())->first();

            if (! $this->hasCompletedProfile($biodata)) {
                Alert::warning('Peringatan', $this->incompleteProfileMessage());
                return redirect()->to(route('biodata.index') . '#step1')->withInput();
            }

            $validator = Validator::make($request->all(), [
                'menyetujui_syarat' => 'accepted',
            ], [
                'menyetujui_syarat.accepted' => 'Anda wajib membaca dan menyetujui syarat dan ketentuan rekrutmen.',
            ]);

            if ($validator->fails()) {
                Alert::warning('Peringatan', 'Anda wajib membaca dan menyetujui syarat dan ketentuan rekrutmen.');
                return redirect()->to(route('biodata.index') . '#step8')->withErrors($validator)->withInput();
            }

            $syaratKetentuan = $this->currentSyaratKetentuan();

            if (! $syaratKetentuan) {
                Alert::error('Error', 'Syarat dan ketentuan rekrutmen belum tersedia. Silakan hubungi admin.');
                return redirect()->to(route('biodata.index') . '#step8')->withInput();
            }

            $dokumenFields = [
                'sim_b_2' => 'SIM B II Umum'
            ];

            $fileNames = [];

            $fileNames = interventionImg($dokumenFields, $biodata, $request);

            $fileNames = $fileNames['files'];
            $oldFiles  = $fileNames['oldFiles'] ?? [];
            $pernyataanUpdates = [
                'status_pernyataan' => $syaratKetentuan->syarat_ketentuan,
            ];

            if (Schema::hasColumn('biodata', 'syarat_ketentuan_id')) {
                $pernyataanUpdates['syarat_ketentuan_id'] = $syaratKetentuan->id;
            }

            if (Schema::hasColumn('biodata', 'status_pernyataan_disetujui_pada')) {
                $pernyataanUpdates['status_pernyataan_disetujui_pada'] = now();
            }

            $biodata->forceFill($pernyataanUpdates)->save();

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

    public function storeProfile(Request $request)
    {
        if (auth()->user()->hasActiveEmploymentStatusLock()) {
            return response()->json([
                'status' => false,
                'message' => $this->accountDataLockedMessage(),
            ], 403);
        }

        $validatedData = $this->validateProfile($request);
        $minatBakatRows = $this->normalizedMinatBakatRows($request);
        $prestasiRows = $this->normalizedPrestasiRows($request);
        $pengalamanKerjaRows = $this->normalizedPengalamanKerjaRows($request);
        $existingBiodata = Biodata::where('user_id', auth()->id())->first();
        $hadStructuredPrestasi = $existingBiodata
            ? $existingBiodata->daftarPrestasi()->exists()
            : false;
        $hobiSummary = Str::limit(
            collect($minatBakatRows)->where('tipe', 'hobi')->pluck('nama')->implode(', '),
            255,
            ''
        );
        $bakatSummary = Str::limit(
            collect($minatBakatRows)->where('tipe', 'bakat')->pluck('nama')->implode(', '),
            255,
            ''
        );

        $hobiSummary = $hobiSummary !== '' ? $hobiSummary : null;
        $bakatSummary = $bakatSummary !== '' ? $bakatSummary : null;

        $prestasiSummary = collect($prestasiRows)->map(function ($row) {
            $field = $row['bidang'] === 'Lainnya' && $row['bidang_lainnya']
                ? $row['bidang_lainnya']
                : $row['bidang'];

            return sprintf(
                '%s - %s (%s, %s, %s)',
                $field,
                $row['jenis_prestasi'],
                $row['peringkat'],
                $row['tingkat'],
                $row['periode']
            );
        })->implode("\n");

        if ($prestasiSummary === '' && $existingBiodata && ! $hadStructuredPrestasi) {
            // Data textarea lama tidak bisa dipetakan otomatis ke field terstruktur.
            $prestasiSummary = $existingBiodata->prestasi;
        }

        DB::transaction(function () use (
            $validatedData,
            $request,
            $minatBakatRows,
            $prestasiRows,
            $pengalamanKerjaRows,
            $hobiSummary,
            $bakatSummary,
            $prestasiSummary
        ) {
            $biodata = Biodata::updateOrCreate(
                [
                    'user_id' => auth()->id()
                ],
                array_merge($this->biodataIdentityDefaults(), [
                // Biodata Pribadi
                'no_ktp' => auth()->user()->no_ktp,
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
                'hobi' => $hobiSummary,
                'bakat' => $bakatSummary,
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
                'prestasi' => $prestasiSummary ?: null,

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

            $biodata->minatBakat()->delete();
            $biodata->minatBakat()->createMany($minatBakatRows);

            $biodata->daftarPrestasi()->delete();
            $biodata->daftarPrestasi()->createMany($prestasiRows);

            $biodata->pengalamanKerja()->delete();
            $biodata->pengalamanKerja()->createMany($pengalamanKerjaRows);
        });

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil disimpan.',
        ]);
    }

    /**
     * Dipertahankan agar endpoint lama tidak menjadi breaking change.
     */
    public function storeStep1to4(Request $request)
    {
        return $this->storeProfile($request);
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

            return redirect()->to(route('biodata.index') . '#step7');
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
                $biodata->ocr_ktp_at = null;
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

        return redirect()->to(route('biodata.index') . '#step7');
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

            if (! $this->hasCompletedProfile($biodata)) {
                return response()->json([
                    'success' => false,
                    'message' => $this->incompleteProfileMessage(),
                ], 422);
            }

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

            $documentUpdates = [
                $uploadedField => $fileName,
            ];

            if ($uploadedField === 'ktp') {
                $documentUpdates['ocr_ktp'] = null;
                $documentUpdates['ocr_ktp_at'] = null;
            }

            if ($uploadedField === 'sim_b_2') {
                $documentUpdates['ocr_sim_b2'] = null;
                $documentUpdates['parsed_sim_b2'] = null;
            }

            $biodata->forceFill(array_merge($this->biodataIdentityDefaults(), $documentUpdates))->save();

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
