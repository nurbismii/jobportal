<?php

use App\Models\Lowongan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;

if (!function_exists('hitungUmur')) {
    function hitungUmur($tanggalLahir)
    {
        $tanggalLahir = new DateTime($tanggalLahir);
        $sekarang = new DateTime();
        $umur = $sekarang->diff($tanggalLahir);
        return $umur->y;
    }
}

if (!function_exists('tanggalIndo')) {
    function tanggalIndo($tanggal)
    {
        // Konversi jika format adalah timestamp (angka saja)
        if (is_numeric($tanggal)) {
            $tanggal = date('Y-m-d', $tanggal);
        }

        // Jika format datetime, ambil hanya bagian tanggalnya
        if (strpos($tanggal, ' ') !== false) {
            $tanggal = explode(' ', $tanggal)[0];
        }

        $bulan = array(
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        );

        $split = explode('-', $tanggal);
        return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    }
}

function interventionImg(array $dokumenFields, $biodata, $request)
{
    $basePath = public_path(Auth::user()->no_ktp . '/dokumen');

    if (!is_dir($basePath)) {
        mkdir($basePath, 0755, true);
    }

    $fileNames = [];
    $oldFiles  = []; // simpan file lama (untuk dihapus belakangan)

    foreach ($dokumenFields as $field => $label) {

        // default: pakai file lama
        $fileNames[$field] = $biodata ? $biodata->{$field} : null;

        if (!$request->hasFile($field)) {
            continue;
        }

        $file = $request->file($field);

        if (!$file->isValid()) {
            continue;
        }

        // simpan nama file lama untuk dihapus
        if ($biodata && $biodata->{$field}) {
            $oldFiles[] = $biodata->{$field};
        }

        $extension = strtolower($file->getClientOriginalExtension());

        // nama file unik & aman
        $fileName = Auth::user()->name . '_' . date('Ymd') . '_' . $field . '.' . $extension;
        $savePath = $basePath . '/' . $fileName;

        // ==== IMAGE ====
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {

            Image::make($file)
                ->resize(1500, null, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                })
                ->encode($extension, 80)
                ->save($savePath);

            $fileNames[$field] = $fileName;
        }

        // ==== PDF ====
        elseif ($extension === 'pdf') {

            // max 50MB
            if ($file->getSize() <= 50 * 1024 * 1024) {
                $file->move($basePath, $fileName);
                $fileNames[$field] = $fileName;
            }
        }
    }

    /**
     * ❗ Jangan hapus file lama di sini
     * Hapus setelah DB sukses (controller / job)
     */

    return [
        'files'    => $fileNames,
        'oldFiles' => $oldFiles
    ];
}

if (!function_exists('tanggalIndoHari')) {
    function tanggalIndoHari($tanggal)
    {
        // Konversi jika format adalah timestamp (angka saja)
        if (is_numeric($tanggal)) {
            $tanggal = date('Y-m-d', $tanggal);
        }

        // Jika format datetime, ambil hanya bagian tanggalnya
        if (strpos($tanggal, ' ') !== false) {
            $tanggal = explode(' ', $tanggal)[0];
        }

        $hari = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu'
        ];

        $bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $split = explode('-', $tanggal);
        $dateObj = DateTime::createFromFormat('Y-m-d', $tanggal);
        $namaHari = $hari[$dateObj->format('l')];

        return $namaHari . ', ' . $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    }
}

if (!function_exists('extractSimB2OnlyOCR')) {
    function extractSimB2OnlyOCR($biodata)
    {
        if ($biodata && $biodata->sim_b_2) {
            $fullPath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->sim_b_2);

            try {
                $apiKey = config('services.ocr_space.key');
                $endpoint = config('services.ocr_space.endpoint', 'https://api.ocr.space/parse/image');

                if (empty($apiKey)) {
                    return ['success' => false, 'message' => 'Konfigurasi OCR.space belum lengkap.'];
                }

                $response = Http::timeout((int) config('services.ocr_space.timeout', 30))
                    ->attach(
                        'file',
                        file_get_contents($fullPath),
                        basename($fullPath)
                    )->post($endpoint, [
                        'apikey' => $apiKey,
                        'language' => 'eng',
                        'OCREngine' => (string) config('services.ocr_space.sim_b2_engine', '2'),
                        'scale' => 'true',
                        'detectOrientation' => 'true',
                        'isOverlayRequired' => 'false',
                    ]);

                if ($response->successful()) {
                    $text = $response->json()['ParsedResults'][0]['ParsedText'] ?? null;

                    $biodata->ocr_sim_b2 = $text;
                    $biodata->save();

                    return ['success' => true, 'message' => 'Berhasil upload sim B2'];
                } else {
                    return ['success' => false, 'message' => 'Upload sim B2 gagal! Silakan coba lagi nanti.'];
                }
            } catch (\Illuminate\Http\Client\RequestException $e) {
                // menangkap error koneksi, timeout, dll.
                return ['success' => false, 'message' => 'Silakan coba beberapa saat lagi.'];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Terjadi kesalahan'];
            }
        }
        return ['success' => false, 'message' => 'File SIM B2 belum tersedia.'];
    }
}

if (!function_exists('hasFilledFields')) {
    function hasFilledFields($source, array $fields)
    {
        foreach ($fields as $field) {
            if (blank(data_get($source, $field))) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('calcutaionStep')) {
    function calcutaionStep($biodata)
    {
        if (!$biodata) {
            return 0;
        }

        $stepFields = [
            1 => [
                'no_ktp',
                'no_telp',
                'no_kk',
                'no_npwp',
                'jenis_kelamin',
                'agama',
                'vaksin',
                'tempat_lahir',
                'tanggal_lahir',
                'provinsi',
                'kabupaten',
                'kecamatan',
                'kelurahan',
                'alamat',
                'kode_pos',
                'rt',
                'rw',
                'hobi',
                'golongan_darah',
                'tinggi_badan',
                'berat_badan',
            ],
            2 => [
                'pendidikan_terakhir',
                'nama_instansi',
                'jurusan',
                'nilai_ipk',
                'tahun_lulus',
            ],
            3 => [
                'nama_ayah',
                'nama_ibu',
                'status_pernikahan',
            ],
            4 => [
                'nama_kontak_darurat',
                'no_telepon_darurat',
                'status_hubungan',
            ],
        ];

        $requiredDocuments = [
            'cv',
            'pas_foto',
            'surat_lamaran',
            'ijazah',
            'ktp',
            'skck',
            'kartu_keluarga',
            'npwp',
            'ak1',
        ];

        $step = 0;

        foreach ($stepFields as $stepNumber => $fields) {
            if (!hasFilledFields($biodata, $fields)) {
                return $step;
            }

            $step = $stepNumber;
        }

        if (!hasFilledFields($biodata, $requiredDocuments)) {
            return $step;
        }

        return 6;
    }
}

if (!function_exists('getLamaranLama')) {
    function getLamaranLama($id)
    {
        if (!$id) {
            return '-';
        }
        return Lowongan::where('id', $id)->value('nama_lowongan');
    }
}


if (!function_exists('compressImageTo1MB')) {
    function compressImageTo1MB($sourcePath, $destinationPath)
    {
        $image = Image::make($sourcePath)->orientate();

        $quality = 90; // mulai dari kualitas tinggi

        do {
            $image->save($destinationPath, $quality);
            $size = filesize($destinationPath);
            $quality -= 5;
        } while ($size > 1024 * 1024 && $quality > 30);

        return $destinationPath;
    }
}

if (!function_exists('deleteImageBiodata')) {
    function deleteImageBiodata($biodata)
    {
        if (!$biodata || empty($biodata->no_ktp)) {
            return;
        }

        $folderKtp = public_path($biodata->no_ktp);

        if (File::exists($folderKtp) && File::isDirectory($folderKtp)) {
            File::deleteDirectory($folderKtp);
        }
    }
}

if (!function_exists('dokumenIcon')) {
    function dokumenIcon($file, $ktp)
    {

        if (!$file) {
            return '-';
        }

        $url = asset($ktp . '/dokumen/' . $file);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $icon = '<i class="fas fa-image text-success"></i>';
        } elseif ($ext == 'pdf') {
            $icon = '<i class="fas fa-file-pdf text-danger"></i>';
        } else {
            $icon = '<i class="fas fa-file"></i>';
        }

        return '
    <a href="javascript:void(0)" 
        class="preview-dokumen"
        data-file="' . $url . '"
        data-ext="' . $ext . '">
        ' . $icon . ' ' . $file . '
    </a>';
    }
}

if (!function_exists('versioned_asset')) {
    function versioned_asset($path, $fallbackVersion = null)
    {
        $path = ltrim($path, '/');
        [$cleanPath] = explode('?', $path, 2);

        $version = null;
        $publicPath = public_path($cleanPath);

        if (is_file($publicPath)) {
            $version = filemtime($publicPath);
        }

        $version = $version ?: $fallbackVersion ?: config('app.asset_version');

        $url = asset($path);

        if (blank($version)) {
            return $url;
        }

        return $url . (Str::contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode((string) $version);
    }
}
