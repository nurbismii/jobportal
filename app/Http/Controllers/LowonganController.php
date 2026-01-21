<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\RiwayatProsesLamaran;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Str;

class LowonganController extends Controller
{
    public function index()
    {
        $lowongans = Lowongan::select('*')
            ->selectRaw("IF(tanggal_berakhir < ?, 'Kadaluwarsa', 'Aktif') as status_lowongan", [Carbon::now()])
            ->where('tanggal_mulai', '<=', Carbon::now()) // hanya yang sudah mulai
            ->having('status_lowongan', '=', 'Aktif')
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

        return view('user.lowongan-kerja.index', compact('lowongans'));
    }

    public function show($id)
    {
        $today = Carbon::now()->toDateTimeString();

        $lowongan = Lowongan::selectRaw("*, IF(tanggal_berakhir < '$today', 'Kadaluwarsa', 'Aktif') as status_lowongan")->findOrFail($id);
        $biodata = Biodata::where('user_id', auth()->id())->first();
        $fieldLabels = $this->getFieldLabels($lowongan->status_sim_b2, $lowongan->status_sio);

        return view('user.lowongan-kerja.show', compact('lowongan', 'biodata', 'fieldLabels'));
    }

    public function store(Request $request)
    {
        $lamaran = Lamaran::where('biodata_id', $request->biodata_id)->latest()->first();

        if ($lamaran) {

            if ($lamaran->loker_id == $request->loker_id) {
                Alert::warning('Peringatan', 'Kamu telah melamar lowongan ini sebelumnya.');
                return redirect()->route('lamaran.index');
            }

            if ($lamaran->status_lamaran == '1') {
                Alert::warning('Peringatan', 'Lamaran kamu sedang dalam proses, harap tunggu hingga proses selesai sebelum melamar lagi.');
                return redirect()->route('lamaran.index');
            }
        }

        $cekBerkas = $this->cekBerkas($request->loker_id);

        if ($cekBerkas) {
            return $cekBerkas; // Jika ada pesan verifikasi, kembalikan ke view verifikasi
        }

        try {
            DB::beginTransaction();

            $lamaran = Lamaran::create([
                'loker_id' => $request->loker_id,
                'biodata_id' => $request->biodata_id,
                'status_lamaran' => '1',
                'status_proses' => 'Lamaran Dikirim',
            ]);

            RiwayatProsesLamaran::create([
                'user_id' => auth()->id(),
                'lamaran_id' => $lamaran->id,
                'tanggal_proses' => Carbon::now(),
                'jam' => Carbon::now()->format('H:i:s'),
                'status_proses' => $lamaran->status_proses,
                'tempat' => 'Online (Website)',
                'pesan' => 'Lamaran telah dikirim pada ' . Carbon::now()->format('d-m-Y H:i:s'),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Gagal', 'Terjadi kesalahan saat mengirim lamaran' . ': ' . $e->getMessage());
            return redirect()->back();
        }

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

    public function cekBerkas($id)
    {
        $userId = auth()->id();

        $nama_sim = null;
        $tanggl_lahir_sim = null;
        $berlaku_sim = null;
        $keterangan_sim = null;

        $aktif = 1;
        $today = Carbon::today()->toDateString();

        $lowongan = Lowongan::selectRaw("*, IF(tanggal_berakhir < '$today', 'Kadaluwarsa', 'Aktif') as status_lowongan")->findOrFail($id);

        if (!$lowongan) {
            Alert::warning('Opps!', 'Lowongan tidak ditemukan, coba beberapa saat lagi');
            return back();
        }

        if (!Auth::user()) {
            Alert::info('Opps!', 'Silahkan login untuk melihat lowongan kerja.');
            return redirect()->route('login');
        }

        $biodata = Biodata::where('user_id', $userId)->first();

        if (!$biodata) {
            Alert::info('Opss!', 'Lengkapi formulir biodata anda terlebih dahulu sebelum melamar lowongan kerja.');
            return redirect()->route('biodata.index');
        }

        if ($biodata->vaksin == null) {
            Alert::info('Opss!', 'Harap mengisi vaksinasi yang pernah dilakukan');
            return redirect()->route('biodata.index');
        }

        // Jika lowongan memerlukan SIM B2
        if ($lowongan->status_sim_b2 == $aktif) {
            if (empty($biodata->sim_b_2)) {
                Alert::info('Opss!', 'Untuk melamar lowongan ini, silakan upload foto SIM B II Umum terlebih dahulu.');
                return redirect()->to(route('biodata.index') . '#step5');
            }

            $res_ocr_simb2 = $this->parseSimB2($biodata);
            $nama_sim = strtoupper($res_ocr_simb2['data']['nama'] ?? '');
            $tanggl_lahir_sim = $res_ocr_simb2['data']['tanggal_lahir'] ?? '';
            $berlaku_sim = $res_ocr_simb2['data']['berlaku_sampai'] ?? null;
        }

        if ($lowongan->status_sio == $aktif) {
            if (empty($biodata->sio)) {
                Alert::info('Opss!', 'Untuk melamar lowongan ini, silakan upload foto SIO terlebih dahulu.');
                return redirect()->to(route('biodata.index') . '#step5');
            }
        }

        // === Bagian Optimasi OCR (pakai cache file) ===
        $filePath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->ktp);

        if (!$biodata->ktp || !file_exists($filePath)) {
            Alert::warning('Gagal', 'File KTP tidak ditemukan, silakan upload KTP terlebih dahulu.');
            return redirect()->to(route('biodata.index') . '#step5');
        }

        // Pastikan folder cache ada
        $cacheDir = storage_path('app/ocr_cache');
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

        $cacheFile = $cacheDir . '/' . $biodata->no_ktp . '_ktp.json';
        $ocrData = null;

        // Masa berlaku cache (3 hari)
        $maxCacheAge = 60 * 60 * 24 * 1;

        // Dapatkan informasi file saat ini
        $fileModified = filemtime($filePath);
        $fileHash = md5_file($filePath);

        // Cek cache valid
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);

            $cacheExpired = (time() - filemtime($cacheFile)) > $maxCacheAge;

            if (
                !$cacheExpired &&
                isset($cache['fileModified'], $cache['fileHash'], $cache['ocrData']) &&
                $cache['fileModified'] === $fileModified &&
                $cache['fileHash'] === $fileHash
            ) {
                // Cache valid
                $ocrData = $cache['ocrData'];
            }
        }

        // Jika cache belum valid → panggil OCR API
        if (!$ocrData) {
            $url = rtrim(config('services.ocr.link'), '/') . '/' . ltrim(config('services.ocr.type'), '/');

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                Alert::error('Gagal', 'URL OCR tidak valid. Silakan periksa konfigurasi.');
                return back();
            }

            $fileContent = @file_get_contents($filePath);
            if ($fileContent === false) {
                Alert::error('Gagal', 'Gagal membaca file KTP. Pastikan file tersedia dan tidak rusak.');
                return redirect()->to(route('biodata.index') . '#step5');
            }

            $response = Http::withToken(config('services.ocr.token'))
                ->withHeaders(['Authentication' => 'bearer ' . config('services.ocr.token')])
                ->attach('file', $fileContent, $biodata->ktp)
                ->put($url);

            if (!$response->successful()) {
                abort(response()->json([
                    'status' => 'error',
                    'http_code' => $response->status(),
                    'reason' => $response->reason(),
                    'body' => $response->body(),
                ], $response->status()));
            }

            $ocrData = $response->json();

            // Simpan hasil OCR + metadata
            file_put_contents($cacheFile, json_encode([
                'fileModified' => $fileModified,
                'fileHash' => $fileHash,
                'ocrData' => $ocrData
            ], JSON_PRETTY_PRINT));
        }

        // === Gunakan hasil OCR ===
        if (!$ocrData) {
            Alert::info('Opss!', 'Silakan lengkapi dokumen pribadi yang dibutuhkan terlebih dahulu.');
            return redirect()->to(route('biodata.index') . '#step5');
        }

        $ocrResult = [
            'nama_ktp' => strtoupper($ocrData['result']['nama']['value'] ?? ''),
            'nik_ktp' => $ocrData['result']['nik']['value'] ?? '',
            'tgl_lahir_ktp' => $ocrData['result']['tanggalLahir']['value'] ?? '',
            'nik_score_ktp' => $ocrData['result']['nik']['score'] ?? '',
            'nama_sim' => $nama_sim,
            'tgl_lahir_sim' => $tanggl_lahir_sim,
            'expired_sim' => $berlaku_sim,
            'keterangan_sim' => $keterangan_sim
        ];

        // Cek expired SIM
        if ($berlaku_sim) {
            $expiredSim = DateTime::createFromFormat('d-m-Y', $berlaku_sim);
            $todayDate = new DateTime();
            $msg_expired_sim = $expiredSim < $todayDate ? 'EXPIRED' : null;
            $biodata->update(['status_sim_b2' => $msg_expired_sim]);
        }

        // Bandingkan data KTP vs SIM
        $msg_name_ktp_vs_sim_b2 = null;
        $msg_date_ktp_vs_sim_b2 = null;

        if ($lowongan->status_sim_b2) {

            // ===== VALIDASI NAMA =====
            if (empty($nama_sim)) {
                $msg_name_ktp_vs_sim_b2 = 'Nama pada SIM B II Umum tidak terbaca.';
            } else {

                $normalizeName = function ($name) {
                    $name = strtoupper(trim($name));
                    $name = preg_replace('/[^A-Z\s]/', '', $name); // hapus titik & simbol
                    return array_values(array_filter(explode(' ', $name)));
                };

                $ktpParts = $normalizeName($ocrResult['nama_ktp'] ?? '');
                $simParts = $normalizeName($ocrResult['nama_sim'] ?? '');

                $valid = true;

                // Nama depan wajib sama
                if (empty($ktpParts[0]) || empty($simParts[0]) || $ktpParts[0] !== $simParts[0]) {
                    $valid = false;
                } else {
                    // Setiap kata SIM harus cocok (full / inisial) dengan KTP
                    foreach ($simParts as $i => $simWord) {
                        $ktpWord = $ktpParts[$i] ?? null;

                        if (!$ktpWord || strpos($ktpWord, $simWord) !== 0) {
                            $valid = false;
                            break;
                        }
                    }
                }

                if (!$valid) {
                    $msg_name_ktp_vs_sim_b2 = 'Nama pada SIM B2 tidak sesuai dengan KTP.';
                }
            }

            // ===== VALIDASI TANGGAL LAHIR =====
            if (empty($tanggl_lahir_sim)) {
                $msg_date_ktp_vs_sim_b2 = 'Tanggal lahir pada SIM B II Umum tidak terbaca.';
            } elseif ($ocrResult['tgl_lahir_ktp'] != $ocrResult['tgl_lahir_sim']) {
                $msg_date_ktp_vs_sim_b2 = 'Tanggal lahir pada SIM B2 tidak sesuai dengan KTP.';
            }
        }

        // Cek field kosong
        $fieldLabels = $this->getFieldLabels($lowongan->status_sim_b2, $lowongan->status_sio);
        $emptyFields = collect($fieldLabels)->filter(fn($label, $field) => empty($biodata->$field))->values()->all();

        $msg_no_ktp = $ocrResult['nik_ktp'] !== $biodata->no_ktp ? 'No KTP tidak sesuai dengan biodata anda.' : null;
        $msg_no_ktp_score = ($ocrResult['nik_score_ktp'] < 50) ? 'KTP tidak jelas, blur, atau tidak dapat dibaca. Silakan perbarui KTP pada dokumen biodata anda.' : null;
        $nikScoreKtp = ($biodata->status_ktp == null && $ocrResult['nik_score_ktp'] < 70) ? 'Verifikasi kepemilikan KTP.' : null;

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

        return; // lanjut ke store()
    }

    public function parseSimB2($biodata, $save = true)
    {
        if (!$biodata->ocr_sim_b2) {
            return ['message' => 'Belum ada hasil OCR untuk diparsing.'];
        }

        $text = $biodata->ocr_sim_b2;
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
        $normalizedText = strtoupper(implode(' ', $lines));

        $isFormatBaru = Str::contains($normalizedText, 'NAMA/NAME') || Str::contains($normalizedText, 'PLACE, DATE OF BIRTH');

        // Lanjutkan dengan parsing seperti sebelumnya
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

        // [masukkan seluruh logic parsing di sini, persis seperti yang sudah kamu buat sebelumnya]
        $afterAlamat = false;

        if ($isFormatBaru) {
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
                    $parsed['jenis_kelamin'] = $upper;
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

                if (empty($parsed['berlaku_sampai'])) {
                    $allDates = [];

                    foreach ($lines as $line) {
                        // Tangkap semua format tanggal: dd-mm-yyyy atau dd/mm/yyyy
                        if (preg_match_all('/\b(\d{2})[\/\-](\d{2})[\/\-](\d{4})\b/', $line, $matches, PREG_SET_ORDER)) {
                            foreach ($matches as $match) {
                                $tanggal = "{$match[1]}-{$match[2]}-{$match[3]}";
                                $timestamp = strtotime(str_replace('/', '-', $tanggal));
                                if ($timestamp !== false) {
                                    $allDates[] = [
                                        'raw' => date('d-m-Y', $timestamp),
                                        'timestamp' => $timestamp
                                    ];
                                }
                            }
                        }
                    }

                    if (!empty($allDates)) {
                        // Ambil tanggal dengan timestamp terbesar
                        usort($allDates, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
                        $parsed['berlaku_sampai'] = end($allDates)['raw']; // hasil: "25-03-2030"
                    }
                }
                // Early break jika semua data sudah terisi
                if ($this->isParsedComplete($parsed)) break;
            }
        } else {
            foreach ($lines as $i => $line) {
                $line = trim($line);
                $upper = strtoupper($line);

                if (preg_match('/1\.\s*(.+)/', $line, $match)) {
                    $parsed['nama'] = ucwords(strtolower($match[1]));
                    continue;
                }

                if (preg_match('/2\.\s*\.?([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})/i', $line, $match)) {
                    $parsed['tempat_lahir'] = ucwords(strtolower($match[1]));
                    $parsed['tanggal_lahir'] = $match[2];
                    continue;
                }

                if (preg_match('/3\.\s*(PRIA|WANITA)/i', $line, $match)) {
                    $parsed['jenis_kelamin'] = strtoupper($match[1]);
                    continue;
                } elseif (preg_match('/\b(PRIA|WANITA)\b/i', $line, $match) && empty($parsed['jenis_kelamin'])) {
                    $parsed['jenis_kelamin'] = strtoupper($match[1]);
                    continue;
                }

                if (preg_match('/4\.\s*(.+)/', $line, $match)) {
                    $parsed['alamat'] = ucwords(strtolower($match[1]));
                    $afterAlamat = true;
                    continue;
                } elseif ($afterAlamat && !preg_match('/^\d+\./', $line) && !preg_match('/\d{2}-\d{2}-\d{4}/', $line)) {
                    $parsed['alamat'] .= ', ' . ucwords(strtolower($line));
                    continue;
                } elseif (preg_match('/5\./', $line)) {
                    $afterAlamat = false;
                }

                if (preg_match('/5\.\s*(.+)/', $line, $match)) {
                    $parsed['pekerjaan'] = ucwords(strtolower($match[1]));
                    continue;
                }

                if (preg_match('/6\.\s*(.+)/', $line, $match)) {
                    $parsed['wilayah'] = ucwords(strtolower($match[1]));
                    continue;
                }

                if (preg_match('/\bPOL(RES|DA|RESTA|SEK)\b/i', $line)) {
                    $parsed['wilayah'] = ucwords(strtolower($line));
                    continue;
                }

                if (preg_match('/\d{2}-\d{2}-\d{4}/', $line, $match) && $match[0] !== $parsed['tanggal_lahir']) {
                    $parsed['berlaku_sampai'] = $match[0];
                }

                if ($this->isParsedComplete($parsed)) break;
            }

            $parsed['alamat'] = rtrim($parsed['alamat'], ', ');
        }

        // Fallback jika data penting belum terbaca
        if (empty($parsed['nama']) || empty($parsed['tanggal_lahir'])) {
            foreach ($lines as $i => $line) {
                $cleanLine = preg_replace('/[^A-Za-z0-9\s.,-]/', '', $line);
                $upper = strtoupper($cleanLine);

                if (preg_match('/^[A-Z]\.?\s+[A-Z]+$/', $cleanLine)) {
                    $parsed['nama'] = ucwords(strtolower($cleanLine));
                }

                if (preg_match('/^([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})$/', $cleanLine, $match)) {
                    $kota = ucwords(strtolower($match[1]));
                    $tanggal = $match[2];

                    // Asumsikan ini tempat dan tanggal pembuatan SIM, **jangan anggap ini berlaku_sampai**
                    if (empty($parsed['tempat_pembuatan']) && empty($parsed['tanggal_pembuatan'])) {
                        $parsed['tempat_pembuatan'] = $kota;
                        $parsed['tanggal_pembuatan'] = $tanggal;
                        continue;
                    }
                }

                // Tangkap semua tanggal lain, pastikan bukan tanggal pembuatan
                if (preg_match('/\d{2}-\d{2}-\d{4}/', $cleanLine, $match)) {
                    $tgl = $match[0];

                    // Jika belum ada tanggal lahir, dan tanggal ini bukan tanggal_pembuatan
                    if (empty($parsed['tanggal_lahir']) && (!isset($parsed['tanggal_pembuatan']) || $tgl !== $parsed['tanggal_pembuatan'])) {
                        $parsed['tanggal_lahir'] = $tgl;
                    }
                    // Jika belum ada berlaku_sampai, dan bukan tanggal lahir & tanggal_pembuatan
                    elseif (empty($parsed['berlaku_sampai']) && $tgl !== $parsed['tanggal_lahir'] && $tgl !== $parsed['tanggal_pembuatan']) {
                        $parsed['berlaku_sampai'] = $tgl;
                    }
                }

                if (in_array($upper, ['PRIA', 'WANITA'])) {
                    $parsed['jenis_kelamin'] = $upper;
                }

                if (empty($parsed['alamat']) && isset($lines[$i + 1], $lines[$i + 2])) {
                    if (preg_match('/JEMBATAN|DUSUN|KAB|DESA|RT|RW/i', $cleanLine)) {
                        $alamat = [$cleanLine];
                        $j = $i + 1;
                        while (isset($lines[$j]) && !preg_match('/POL(RES|DA|RESTA|SEK)|\d{2}-\d{2}-\d{4}/', $lines[$j])) {
                            $alamat[] = preg_replace('/[^A-Za-z0-9\s.,-]/', '', $lines[$j]);
                            $j++;
                        }
                        $parsed['alamat'] = ucwords(strtolower(implode(', ', $alamat)));
                    }
                }

                if (preg_match('/(SWASTA|WIRASWASTA|PEGAWAI|SISWA|MAHASISWA)/i', $cleanLine, $match)) {
                    $parsed['pekerjaan'] = ucfirst(strtolower($match[1]));
                }

                if (preg_match('/POL(RES|DA|RESTA|SEK)/i', $cleanLine)) {
                    $parsed['wilayah'] = ucwords(strtolower($cleanLine));
                }

                if (preg_match('/\d{2}-\d{2}-\d{4}/', $cleanLine, $match)) {
                    if ($match[0] !== $parsed['tanggal_lahir']) {
                        $parsed['berlaku_sampai'] = $match[0];
                    }
                }

                if (preg_match('/Nama\s*:\s*(.+)/i', $line, $match)) {
                    $parsed['nama'] = ucwords(strtolower($match[1]));
                }

                if (preg_match('/Alamat\s*:\s*(.+)/i', $line, $match)) {
                    $alamat = [$match[1]];
                    $j = $i + 1;
                    while (isset($lines[$j]) && !preg_match('/^\s*Tempat|Tgi\.Lahir|Peker|No\.|Berlaku/i', $lines[$j])) {
                        $alamat[] = $lines[$j];
                        $j++;
                    }
                    $parsed['alamat'] = ucwords(strtolower(implode(', ', $alamat)));
                }

                if (preg_match('/Tempat.*:\s*(.+)/i', $line, $match)) {
                    $parsed['tempat_lahir'] = ucwords(strtolower($match[1]));
                }

                if (preg_match('/Tgi\.?\.?Lahir\s*:\s*(\d{2}-\d{2}-\d{4})/i', $line, $match)) {
                    $parsed['tanggal_lahir'] = $match[1];
                }

                if (preg_match('/Peker.?aan\s*:\s*(.+)/i', $line, $match)) {
                    $parsed['pekerjaan'] = ucwords(strtolower($match[1]));
                }

                if (preg_match('/Berlaku.*:\s*(\d{2}-\d{2}-\d{4})/i', $line, $match)) {
                    $parsed['berlaku_sampai'] = $match[1];
                }

                if (preg_match('/\bP.?RIA\b/i', $line)) {
                    $parsed['jenis_kelamin'] = 'PRIA';
                } elseif (preg_match('/\bW.?ANITA\b/i', $line)) {
                    $parsed['jenis_kelamin'] = 'WANITA';
                }

                // Tangkap nama satu baris di atas baris yang memuat tempat, tanggal lahir
                // Jika nama dan tempat/tanggal lahir masih kosong, coba deteksi pola fallback format baru
                if (empty($parsed['nama']) && empty($parsed['tempat_lahir'])) {
                    foreach ($lines as $i => $line) {
                        $cleanLine = preg_replace('/[^A-Za-z0-9\s.,-]/', '', $line);
                        $upperLine = strtoupper(trim($cleanLine));

                        // Cari baris yang merupakan tempat dan tanggal lahir
                        if (preg_match('/([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})/', $upperLine, $match)) {
                            $kota = ucwords(strtolower(trim($match[1])));
                            $tanggal = $match[2];

                            // Validasi tahun masuk akal sebagai tanggal lahir
                            $year = (int)substr($tanggal, -4);
                            if ($year < 2010) {
                                $parsed['tempat_lahir'] = $kota;
                                $parsed['tanggal_lahir'] = $tanggal;

                                // Cari nama di baris sebelumnya, jika huruf kapital semua
                                if ($i > 0) {
                                    $namaLine = trim($lines[$i - 1]);
                                    $namaClean = preg_replace('/[^A-Za-z\s]/', '', $namaLine);
                                    if (preg_match('/^[A-Z\s]{3,}$/', strtoupper($namaClean))) {
                                        $parsed['nama'] = ucwords(strtolower($namaClean));
                                    }
                                }

                                break; // selesai parsing nama dan TTL
                            }
                        }

                        if (empty($parsed['nama'])) {
                            foreach ($lines as $i => $line) {
                                $cleanLine = preg_replace('/[^A-Za-z\s]/', '', $line);
                                $upper = strtoupper($cleanLine);

                                // Abaikan baris yang terlalu umum
                                $nonNamePatterns = [
                                    'INDONESIA',
                                    'SURAT IZIN MENGEMUDI',
                                    'SURAT IZIN MENOL',
                                    'DRIVING LICENSE',
                                    'SIM',
                                    'POLRI',
                                    'REPUBLIK INDONESIA',
                                    'JENIS KELAMIN',
                                    'GOL DARAH',
                                    'ALAMAT',
                                    'PEKERJAAN',
                                    'DITERBITKAN OLEH',
                                    'KENDARAAN',
                                    'BILUMUM',
                                    'IA MAINA NE'
                                ];

                                if (in_array($upper, $nonNamePatterns) || strlen($cleanLine) < 4) {
                                    continue;
                                }

                                // Coba ambil baris sebelum tempat lahir
                                if (!empty($parsed['tanggal_lahir']) && isset($lines[$i + 1])) {
                                    if (preg_match('/\d{2}-\d{2}-\d{4}/', $lines[$i + 1])) {
                                        // Baris ini kemungkinan besar adalah nama
                                        $parsed['nama'] = ucwords(strtolower($cleanLine));
                                        break;
                                    }
                                }

                                // Atau baris yang huruf besar semua dan terdiri dari 2 kata
                                if (preg_match('/^[A-Z\s]{4,}$/', $upper) && substr_count($upper, ' ') <= 2) {
                                    $parsed['nama'] = ucwords(strtolower($cleanLine));
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($this->isParsedComplete($parsed)) break;
            }
        }

        if (
            !empty($parsed['tanggal_pembuatan']) &&
            !empty($parsed['tanggal_lahir']) &&
            $parsed['tanggal_lahir'] === $parsed['berlaku_sampai']
        ) {
            // Asumsikan tanggal_pembuatan adalah tanggal lahir
            $parsed['tanggal_lahir'] = $parsed['tanggal_pembuatan'];
            $parsed['tempat_lahir'] = $parsed['tempat_pembuatan'];

            // Reset tanggal_pembuatan karena ternyata salah
            $parsed['tanggal_pembuatan'] = '';
            $parsed['tempat_pembuatan'] = '';
        }

        if ($save && method_exists($biodata, 'save')) {
            $biodata->parsed_sim_b2 = $parsed;
            $biodata->save();
        }

        return [
            'message' => 'Parsing berhasil.',
            'data' => $parsed,
        ];
    }

    /**
     * Mengecek apakah semua data penting sudah terisi
     */
    private function isParsedComplete(array $parsed): bool
    {
        return !empty($parsed['nama']) &&
            !empty($parsed['tanggal_lahir']) &&
            !empty($parsed['alamat']) &&
            !empty($parsed['jenis_kelamin']) &&
            !empty($parsed['tempat_lahir']) &&
            !empty($parsed['pekerjaan']) &&
            !empty($parsed['wilayah']) &&
            !empty($parsed['berlaku_sampai']);
    }


    public function getFieldLabels($status_sim_b2 = 0, $status_sio = 0)
    {
        $fieldLabels = [
            // Identitas Pribadi
            'no_ktp' => 'Nomor KTP',
            'no_telp' => 'Nomor Telepon',
            'no_kk' => 'Nomor Kartu Keluarga',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'vaksin' => 'Vaksin',

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

        // Tambahkan SIM B II jika status_sim_b2 aktif (= 1)
        if ($status_sim_b2 == 1) {
            $fieldLabels['sim_b_2'] = 'SIM B II Umum';
        }

        // Tambahkan SIO jika status_sio aktif (= 1)
        if ($status_sio == 1) {
            $fieldLabels['sio'] = 'SIO (Surat Izin Operator)';
        }

        return $fieldLabels;
    }
}
