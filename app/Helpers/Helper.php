<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

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

function interventionImg($dokumenFields, $biodata, $request)
{
    $folderPath = public_path(Auth::user()->no_ktp . '/dokumen');

    if (!file_exists($folderPath)) {
        mkdir($folderPath, 0777, true);
    }

    $fileNames = []; // 💡 pastikan ini diinisialisasi

    foreach ($dokumenFields as $field => $label) {
        if ($request->hasFile($field)) {
            if ($biodata && $biodata->{$field}) {
                $oldFilePath = $folderPath . '/' . $biodata->{$field};
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            $file = $request->file($field);
            $slugName = Str::slug(Auth::user()->name, '_');
            $timestamp = now()->format('mY');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = "{$slugName}-{$label}-{$timestamp}.{$extension}";
            $savePath = $folderPath . '/' . $fileName;

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $image = Image::make($file);

                if ($image->width() > 1500) {
                    $image->resize(1500, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                $quality = 85;
                do {
                    $image->encode($extension, $quality);
                    $sizeKB = strlen((string)$image) / 1024;
                    $quality -= 5;
                } while ($sizeKB > 1024 && $quality > 10);

                $image->save($savePath);
                $fileNames[$field] = $fileName;
            } elseif ($extension === 'pdf') {
                $sizeKB = $file->getSize() / 1024;
                if ($sizeKB <= 2048) {
                    $file->move($folderPath, $fileName);
                    $fileNames[$field] = $fileName;
                } else {
                    $fileNames[$field] = null;
                }
            } else {
                $fileNames[$field] = null;
            }
        } else {
            $fileNames[$field] = $biodata ? $biodata->{$field} : null;
        }
    }

    return $fileNames;
}

if (!function_exists('extractSimB2OnlyOCR')) {
    function extractSimB2OnlyOCR($biodata)
    {
        if ($biodata && $biodata->sim_b_2) {
            $fullPath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->sim_b_2);

            $response = Http::attach(
                'file',
                file_get_contents($fullPath),
                basename($fullPath)
            )->post('https://api.ocr.space/parse/image', [
                'apikey' => 'K82052672988957',
                'language' => 'eng',
                'OCREngine' => '2',
                'scale' => 'true',
                'detectOrientation' => 'true',
                'isOverlayRequired' => 'false',
            ]);

            $text = $response->json()['ParsedResults'][0]['ParsedText'] ?? null;

            $biodata->ocr_sim_b2 = $text;
            $biodata->save();

            return ['message' => 'OCR berhasil, silakan lanjutkan parsing.'];
        }

        return ['message' => 'Gagal menemukan file SIM B2'];
    }
}
