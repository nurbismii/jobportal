<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Str;

class LowonganController extends Controller
{
    public function index()
    {
        $lowongans = Lowongan::orderBy('id', 'desc')->get();

        return view('user.lowongan-kerja.index', compact('lowongans'));
    }

    public function show($id)
    {
        $userId = auth()->id();
        $cacheKey = 'ocr_result_user_' . $userId;
        $resetCooldownKey = 'ocr_reset_cooldown_user_' . $userId;

        // Batasi reset OCR hanya 1x tiap 5 menit (300 detik)
        if (request()->query('refresh') === 'true') {
            if (Cache::has($resetCooldownKey)) {
                $secondsLeft = Cache::get($resetCooldownKey) - time();
                if ($secondsLeft > 0) {
                    Alert::warning('Tunggu dulu', "Anda bisa reset OCR lagi setelah $secondsLeft detik.");
                    return redirect()->back();
                }
            }
            // Hapus cache hasil OCR dan set cooldown 5 menit
            Cache::forget($cacheKey);
            Cache::put($resetCooldownKey, time() + 300, 300); // cache 5 menit
        }

        $biodata = Biodata::where('user_id', auth()->id())->first();

        if (!$biodata) {
            Alert::info('Login dulu yuk!', 'Silahkan login untuk melihat lowongan kerja.');
            return redirect()->route('login');
        }

        // Dapatkan data SIM B2 dari OCR
        $res_ocr_simb2 = $this->extractSimB2($biodata);

        // Jika ada query ?refresh=true, hapus cache manual
        if (request()->query('refresh') === 'true') {
            Cache::forget($cacheKey);
        }

        // Ambil data OCR dari cache, jika belum ada jalankan closure untuk panggil API dan simpan cache selama 12 jam
        $ocrData = Cache::remember($cacheKey, now()->addHours(12), function () use ($biodata) {

            $filePath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->ktp);

            if (!file_exists($filePath)) {
                abort(404, 'File KTP tidak ditemukan.');
            }

            if ($biodata->ktp) {


                $url = config('services.ocr.link') . '/' . config('services.ocr.type');

                $response = Http::withToken(config('services.ocr.token'))
                    ->withHeaders([
                        'Authentication' => 'bearer ' . config('services.ocr.token'),
                    ])
                    ->attach('file', file_get_contents($filePath), $biodata->ktp)
                    ->put($url);

                if (!$response->successful()) {
                    abort(response()->json([
                        'status' => 'error',
                        'http_code' => $response->status(),
                        'reason' => $response->reason(),
                        'headers' => $response->headers(),
                        'body' => $response->body(),
                    ], $response->status()));
                }

                return $response->json();
            }
        });

        $ocrResult = [
            'nama_ktp' => strtoupper($ocrData['result']['nama']['value']) ?? null,
            'nik_ktp' => $ocrData['result']['nik']['value'] ?? null,
            'tgl_lahir_ktp' => $ocrData['result']['tanggalLahir']['value'] ?? null,
            'nik_score_ktp' => $ocrData['result']['nik']['score'] ?? null,
            'nama_sim' => strtoupper($res_ocr_simb2['data']['nama']) ?? null,
            'tgl_lahir_sim' => $res_ocr_simb2['data']['tanggal_lahir'] ?? null,
            'expired_sim' => $res_ocr_simb2['data']['berlaku_sampai'] ?? null,
        ];

        $msg_expired_sim = $ocrResult['expired_sim'] < date('Y-m-d')
            ? 'EXPIRED'
            : null;

        if ($msg_expired_sim) {
            $biodata->update([
                'status_sim_b2' => $msg_expired_sim
            ]);
        }

        // Compare OCR data
        $msg_name_ktp_vs_sim_b2 = $ocrResult['nama_sim'] != $ocrResult['nama_ktp']
            ? 'Nama pada KTP tidak sesuai dengan nama pada SIM B2.'
            : null;

        $msg_date_ktp_vs_sim_b2 = $ocrResult['tgl_lahir_ktp'] != $ocrResult['tgl_lahir_sim']
            ? 'Tanggal lahir pada KTP tidak sesuai dengan tanggal lahir pada SIM B2.'
            : null;

        $lowongan = Lowongan::findOrFail($id);

        $fieldLabels = $this->getFieldLabels($lowongan->status_sim_b2);

        $emptyFields = collect($fieldLabels)->filter(function ($label, $field) use ($biodata) {
            return empty($biodata->$field);
        })->values()->all();

        $msg_no_ktp = $ocrResult['nik_ktp'] !== $biodata->no_ktp
            ? 'No KTP tidak sesuai dengan biodata anda.'
            : null;

        $msg_no_ktp_score = $ocrResult['nik_score_ktp'] < 70
            ? 'KTP tidak jelas, blur atau tidak dapat dibaca. Silakan perbarui KTP pada dokumen biodata anda.'
            : null;

        $nikScoreKtp = ($biodata->status_ktp == null && $ocrResult['nik_score_ktp'] < 69)
            ? 'Verifikasi kepemilikan KTP'
            : null;

        if (count($emptyFields) || $msg_no_ktp || $msg_no_ktp_score || $nikScoreKtp || $msg_name_ktp_vs_sim_b2 || $msg_date_ktp_vs_sim_b2) {
            return view('user.lowongan-kerja.verifikasi', [
                'emptyFields' => $emptyFields,
                'msg_no_ktp' => $msg_no_ktp,
                'msg_no_ktp_score' => $msg_no_ktp_score,
                'msg_nik_score' => $nikScoreKtp,
                'msg_name_ktp_vs_sim_b2' => $msg_name_ktp_vs_sim_b2,
                'msg_date_ktp_vs_sim_b2' => $msg_date_ktp_vs_sim_b2,
                'biodata' => $biodata,
            ]);
        }

        return view('user.lowongan-kerja.show', compact('lowongan', 'biodata'));
    }


    public function store(Request $request)
    {
        $lamaran = Lamaran::where('biodata_id', $request->biodata_id)
            ->where('loker_id', $request->loker_id)
            ->first();

        if ($lamaran) {
            Alert::warning('Peringatan', 'Anda sudah melamar pekerjaan ini sebelumnya.');
            return redirect()->back();

            if ($lamaran->status_lamaran == '1') {
                Alert::warning('Peringatan', 'Saat ini Anda dalam proses lamaran, tidak dapat melamar lebih dari satu lowongan.');
                return redirect()->back();
            }
        }

        Lamaran::create([
            'loker_id' => $request->loker_id,
            'biodata_id' => $request->biodata_id,
            'status_lamaran' => '1',
            'status_proses' => 'Lamaran Dikirim',
        ]);

        Alert::success('Lamaran Anda Sudah Kami Terima', 'Terima kasih telah melamar pekerjaan di perusahaan kami. Kami akan segera memproses lamaran Anda.');
        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $biodata = Biodata::findOrFail($id);
        $biodata->update([
            'status_ktp' => 'Verifikasi ulang' . ' : ' . $request->status_ktp,
        ]);

        Alert::success('Berhasil', 'No KTP berhasil berhasil diinput secara manual.');
        return redirect()->back();
    }

    public function extractSimB2($biodata)
    {
        if ($biodata && $biodata->sim_b_2) {
            $fullPath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->sim_b_2);

            $response = Http::attach(
                'file',
                file_get_contents($fullPath),
                basename($fullPath)
            )->post('https://api.ocr.space/parse/image', [
                'apikey' => 'K82052672988957',
                'language' => 'eng', // Gunakan juga 'ind' jika perlu
            ]);

            $result = $response->json();
            $text = $result['ParsedResults'][0]['ParsedText'] ?? '';
            $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));

            // Normalisasi huruf besar untuk pendeteksian pola
            $normalizedText = strtoupper(implode(' ', $lines));

            // Tentukan apakah ini format baru atau lama
            $isFormatBaru = Str::contains($normalizedText, 'NAMA/NAME') || Str::contains($normalizedText, 'PLACE, DATE OF BIRTH');

            $parsed = [
                'nama' => '',
                'tempat_lahir' => '',
                'tanggal_lahir' => '',
                'jenis_kelamin' => '',
                'alamat' => '',
                'pekerjaan' => '',
                'wilayah' => '',
                'berlaku_sampai' => '',
            ];

            $ttlFound = false;
            $afterAlamat = false;

            if ($isFormatBaru) {
                // FORMAT BARU
                foreach ($lines as $i => $line) {
                    $upper = strtoupper($line);

                    if (Str::contains($upper, 'NAMA') && isset($lines[$i + 1])) {
                        $parsed['nama'] = ucwords(strtolower($lines[$i + 1]));
                    }

                    if (preg_match('/([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})/', $line, $match)) {
                        $parsed['tempat_lahir'] = ucwords(strtolower($match[1]));
                        $parsed['tanggal_lahir'] = $match[2];
                    }

                    if (Str::contains($upper, 'JENIS KELAMIN') || in_array($upper, ['PRIA', 'WANITA'])) {
                        $parsed['jenis_kelamin'] = 'PRIA';
                    }

                    if (Str::contains($upper, 'ALAMAT') && isset($lines[$i + 1])) {
                        $alamat = [];
                        for ($j = $i + 1; $j <= $i + 3 && isset($lines[$j]); $j++) {
                            $alamat[] = $lines[$j];
                        }
                        $parsed['alamat'] = implode(', ', $alamat);
                    }

                    if (Str::contains($upper, 'PEKERJAAN') && isset($lines[$i + 1])) {
                        $parsed['pekerjaan'] = ucwords(strtolower($lines[$i + 1]));
                    }

                    if (Str::contains($upper, 'DITERBITKAN OLEH') && isset($lines[$i + 1])) {
                        $parsed['wilayah'] = ucwords(strtolower($lines[$i + 1]));
                    }

                    if (preg_match('/\d{2}-\d{2}-\d{4}/', $line)) {
                        $parsed['berlaku_sampai'] = $line;
                    }
                }
            } else {
                // FORMAT LAMA
                foreach ($lines as $line) {
                    $line = trim($line);
                    $upper = strtoupper($line);

                    // 1. Nama
                    if (preg_match('/1\.\s*(.+)/', $line, $match)) {
                        $parsed['nama'] = ucwords(strtolower($match[1]));
                        continue;
                    }

                    // 2. Tempat, Tanggal Lahir
                    if (preg_match('/2\.\s*\.?([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})/i', $line, $match)) {
                        $parsed['tempat_lahir'] = ucwords(strtolower($match[1]));
                        $parsed['tanggal_lahir'] = $match[2];
                        $ttlFound = true;
                        continue;
                    }

                    // 3. Jenis Kelamin
                    if (preg_match('/3\.\s*(PRIA|WANITA)/i', $line, $match)) {
                        $parsed['jenis_kelamin'] = strtoupper($match[1]);
                        continue;
                    } elseif (preg_match('/\b(PRIA|WANITA)\b/i', $line, $match) && empty($parsed['jenis_kelamin'])) {
                        $parsed['jenis_kelamin'] = strtoupper($match[1]);
                        continue;
                    }

                    // 4. Alamat
                    if (preg_match('/4\.\s*(.+)/', $line, $match)) {
                        $parsed['alamat'] = ucwords(strtolower($match[1]));
                        $afterAlamat = true;
                        continue;
                    } elseif ($afterAlamat && !preg_match('/^\d+\./', $line) && !preg_match('/\d{2}-\d{2}-\d{4}/', $line)) {
                        $parsed['alamat'] .= ', ' . ucwords(strtolower($line));
                        continue;
                    } elseif (preg_match('/5\./', $line)) {
                        $afterAlamat = false; // stop parsing alamat
                    }

                    // 5. Pekerjaan
                    if (preg_match('/5\.\s*(.+)/', $line, $match)) {
                        $parsed['pekerjaan'] = ucwords(strtolower($match[1]));
                        continue;
                    }

                    // 6. Wilayah
                    if (preg_match('/6\.\s*(.+)/', $line, $match)) {
                        $parsed['wilayah'] = ucwords(strtolower($match[1]));
                        continue;
                    }

                    // Wilayah alternatif: POLRES...
                    if (preg_match('/\bPOL(RES|DA|SEK)\b/i', $line)) {
                        $parsed['wilayah'] = ucwords(strtolower($line));
                        continue;
                    }

                    // Berlaku Sampai
                    if (
                        preg_match('/\d{2}-\d{2}-\d{4}/', $line, $match) &&
                        $match[0] !== $parsed['tanggal_lahir']
                    ) {
                        $parsed['berlaku_sampai'] = $match[0];
                    }
                }

                // Rapikan alamat
                $parsed['alamat'] = rtrim($parsed['alamat'], ', ');
            }

            return [
                'ocr_text' => $text,
                'data' => $parsed,
            ];
        }

        return [
            'ocr_text' => null,
            'data' => null,
        ];
    }

    public function getFieldLabels($status_sim_b2)
    {
        if ($status_sim_b2 == '0') {

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
                'skck' => 'SKCK (Surat Keterangan Catatan Kepolisian)',
                'sertifikat_vaksin' => 'Sertifikat Vaksin',
                'kartu_keluarga' => 'Kartu Keluarga (KK)',
                'npwp' => 'NPWP (Nomor Pokok Wajib Pajak)',
                'ak1' => 'Kartu AK1 (Kartu Pencari Kerja)',
            ];
        } else {

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
                'skck' => 'SKCK (Surat Keterangan Catatan Kepolisian)',
                'sertifikat_vaksin' => 'Sertifikat Vaksin',
                'kartu_keluarga' => 'Kartu Keluarga (KK)',
                'npwp' => 'NPWP (Nomor Pokok Wajib Pajak)',
                'ak1' => 'Kartu AK1 (Kartu Pencari Kerja)',
            ];
        }
        return $fieldLabels;
    }
}
