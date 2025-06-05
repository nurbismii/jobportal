<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
            Alert::info('Informasi', 'Silahkan login terlebih dahulu untuk melihat lowongan kerja.');
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
        });

        $ocrResult = [
            'nama_ktp' => $ocrData['result']['nama']['value'] ?? null,
            'nik_ktp' => $ocrData['result']['nik']['value'] ?? null,
            'tgl_lahir_ktp' => $ocrData['result']['tanggalLahir']['value'] ?? null,
            'nik_score_ktp' => $ocrData['result']['nik']['score'] ?? null,
            'nama_sim' => $res_ocr_simb2['data']['nama'] ?? null,
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
        $msg_name_ktp_vs_sim_b2 = $ocrResult['nama_sim'] !== $ocrResult['nama_ktp']
            ? 'Nama pada KTP tidak sesuai dengan nama pada SIM B2.'
            : null;

        $msg_date_ktp_vs_sim_b2 = $ocrResult['tgl_lahir_ktp'] !== $ocrResult['tgl_lahir_sim']
            ? 'Tanggal lahir pada KTP tidak sesuai dengan tanggal lahir pada SIM B2.'
            : null;

        $lowongan = Lowongan::findOrFail($id);

        $fieldLabels = $this->getFieldLabels($lowongan->status_sim_b2);

        $emptyFields = collect($fieldLabels)->filter(function ($label, $field) use ($biodata) {
            return empty($biodata->$field);
        })->values()->all();

        $msg_no_ktp = $ocrResult['nik_ktp'] !== $biodata->no_ktp
            ? 'No KTP yang diambil dari hasil OCR tidak sesuai dengan data biodata Anda.'
            : null;

        $msg_no_ktp_score = $ocrResult['nik_score_ktp'] < 70
            ? 'Skor kecocokan NIK hasil OCR terlalu rendah. Silakan perbarui KTP pada dokumen biodata Anda.'
            : null;

        $nikScoreKtp = ($biodata->status_ktp == null && $ocrResult['nik_score_ktp'] < 69)
            ? 'Skor kecocokan NIK hasil OCR terlalu rendah.'
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
            'status_ktp' => 'No KTP diinput secara manual diakibatkan OCR tidak valid' . ' : ' . $request->status_ktp,
        ]);

        Alert::success('Berhasil', 'No KTP berhasil berhasil diinput secara manual.');
        return redirect()->back();
    }

    public function extractSimB2($biodata)
    {
        $fullPath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->sim_b_2);

        $response = Http::attach(
            'file',
            file_get_contents($fullPath),
            basename($fullPath)
        )->post('https://api.ocr.space/parse/image', [
            'apikey' => 'K82052672988957', // Ganti jika perlu
            'language' => 'eng', // atau 'ind'
        ]);

        $result = $response->json();

        $text = $result['ParsedResults'][0]['ParsedText'] ?? '';
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));

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

        $indexKelamin = null;
        $indexTTL = null;

        foreach ($lines as $i => $line) {
            $upper = strtoupper($line);

            // Nama setelah Narnamame
            if ($upper === 'NARNAMAME' && isset($lines[$i + 1])) {
                $parsed['nama'] = ucwords(strtolower($lines[$i + 1]));
            }

            // Tempat dan Tanggal Lahir
            if (preg_match('/([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})/', $line, $match)) {
                $parsed['tempat_lahir'] = ucwords(strtolower($match[1]));
                $parsed['tanggal_lahir'] = $match[2];
                $indexTTL = $i;
            }

            // Jenis Kelamin
            if (in_array($upper, ['PRIA', 'WANITA'])) {
                $parsed['jenis_kelamin'] = $upper;
                $indexKelamin = $i;
            }

            // Pekerjaan
            if (preg_match('/\b(SWASTA|PELAJAR|KARYAWAN|WIRASWASTA|PNS)\b/i', $line)) {
                $parsed['pekerjaan'] = ucwords(strtolower($line));
            }

            // Wilayah (POLRES ...)
            if (
                preg_match('/\bPOL(RES|DA|SEK)\b/i', $line) &&
                strlen($line) <= 50
            ) {
                $parsed['wilayah'] = ucwords(strtolower($line));
            }

            // Berlaku sampai (tanggal terakhir dengan format valid)
            if (preg_match('/\d{2}-\d{2}-\d{4}/', $line)) {
                $parsed['berlaku_sampai'] = $line;
            }
        }

        // PARSING ALAMAT: Ambil 3 baris sebelum dan sesudah 'PRIA' atau TTL
        $alamatKandidat = [];

        foreach ([$indexKelamin, $indexTTL] as $idx) {
            if ($idx === null) continue;

            $start = max(0, $idx - 3);
            $end = min(count($lines) - 1, $idx + 3);

            for ($j = $start; $j <= $end; $j++) {
                $line = trim($lines[$j]);

                $stopwords = ['indonesia', 'surat', 'izin', 'mengemudi', 'umum', 'pria', 'wanita', 'swasta', 'pelajar', 'driving license'];
                $namaPattern = preg_replace('/\s+/', '\s*', preg_quote(strtolower($parsed['nama']), '/'));

                if (
                    preg_match('/\b(DUSUN|JL\.|JALAN|DESA|KEL\.?|KELURAHAN|KEC\.?|KECAMATAN|KAB\.?|KABUPATEN|RT|RW|WUNDULAKO|KOTA|WUNDULAKO)\b/i', $line)
                    && !in_array(strtolower($line), $stopwords)
                    && !preg_match("/\d{2}-\d{2}-\d{4}/", $line) // hindari tanggal
                    && !preg_match("/$namaPattern/", strtolower($line)) // hindari nama
                    && strlen($line) >= 4 && strlen($line) <= 40
                ) {
                    $alamatKandidat[] = ucwords(strtolower($line));
                }
            }
        }

        $parsed['alamat'] = implode(', ', array_unique($alamatKandidat));

        return [
            'ocr_text' => $text,
            'data' => $parsed
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
