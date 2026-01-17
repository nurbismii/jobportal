<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Hris\Divisi;
use App\Models\Hris\Kabupaten;
use App\Models\Hris\Kecamatan;
use App\Models\Hris\Kelurahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class ApiController extends Controller
{
    public function fetchKabupaten($id)
    {
        $kabupaten = Kabupaten::where('id_provinsi', $id)->get();
        return response()->json($kabupaten);
    }

    public function fetchKecamatan($id)
    {
        $kecamatan = Kecamatan::where('id_kabupaten', $id)->get();
        return response()->json($kecamatan);
    }

    public function fetchKelurahan($id)
    {
        $kecamatan = Kelurahan::where('id_kecamatan', $id)->get();
        return response()->json($kecamatan);
    }

    public function getByDepartemen($departemen_id)
    {
        $divisi = Divisi::where('departemen_id', $departemen_id)->orderBy('nama_divisi', 'asc')->get();
        return response()->json($divisi);
    }

    public function getLowongan($ptk_id = null)
    {
        $query = \App\Models\Lowongan::select('id', 'nama_lowongan')->where('permintaan_tenaga_kerja_id', $ptk_id)->get();

        return response()->json($query);
    }

    public function ocrSimB2(Request $request)
    {
        if (!$request->hasFile('sim_b_2')) {
            return response()->json(['success' => false, 'message' => 'File SIM B2 tidak ditemukan.']);
        }

        $file = $request->file('sim_b_2');

        $path = $file->storeAs('temp_ocr', uniqid() . '.' . $file->getClientOriginalExtension(), 'public');
        $fullPath = storage_path('app/public/' . $path);

        $compressedPath = storage_path('app/public/temp_ocr/compressed_' . basename($path));

        // 🔥 KOMPRES FILE ≤ 1 MB
        compressImageTo1MB($fullPath, $compressedPath);

        try {
            // Panggil API OCR
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($compressedPath), basename($compressedPath))
                ->post('https://api.ocr.space/parse/image', [
                    'apikey' => 'K82052672988957',
                    'language' => 'eng',
                    'OCREngine' => '2',
                    'scale' => 'true',
                    'detectOrientation' => 'true',
                    'isOverlayRequired' => 'false',
                ]);

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => 'OCR gagal.']);
            }

            $text = $response->json()['ParsedResults'][0]['ParsedText'] ?? '';
            $biodata = new \stdClass(); // dummy biodata
            $biodata->ocr_sim_b2 = $text;

            Biodata::where('user_id', auth()->id())->update([
                'ocr_sim_b2'    => $text,
            ]);

            // Gunakan parser
            $parsedResult = app()->call([new \App\Http\Controllers\LowonganController, 'parseSimB2'], ['biodata' => $biodata, 'save' => false]);

            return response()->json([
                'success' => true,
                'data' => $parsedResult['data'],
            ]);
        } catch (\Throwable $e) {
            Log::info('OCR SIM B2 Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan, coba beberapa saat lagi.']);
        } finally {
            \Storage::disk('public')->delete([$path, 'temp_ocr/' . basename($compressedPath)]);
        }
    }

    public function ocrKtp(Request $request)
    {
        $biodata = Biodata::where('user_id', auth()->id())->first();

        // === Jika OCR masih valid ===
        if ($biodata && $biodata->isValidOcrKtp()) {
            Alert::info('Info', 'Menggunakan data OCR KTP yang sudah ada dan masih berlaku.');
            return redirect()->back();
        }

        if (!$request->hasFile('ktp')) {
            return response()->json([
                'success' => false,
                'message' => 'File KTP tidak ditemukan.'
            ]);
        }

        // === Validasi konfigurasi OCR ===
        $url   = rtrim(config('services.ocr.link'), '/') . '/' . ltrim(config('services.ocr.type'), '/');
        $token = config('services.ocr.token');

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL) || empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi OCR tidak valid.'
            ]);
        }

        $file = $request->file('ktp');
        $cacheDir = storage_path('app/ocr_cache');

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $fileHash = md5_file($file->getRealPath()) ?: uniqid();
        $cacheFile = "{$cacheDir}/{$fileHash}.json";

        // === Gunakan cache jika ada ===
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {

            $cachedData = json_decode(file_get_contents($cacheFile), true);

            Biodata::where('user_id', auth()->id())->update([
                'ocr_ktp'    => json_encode($cachedData, JSON_PRETTY_PRINT),
                'ocr_ktp_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'cached'  => true,
                'data'    => $cachedData,
            ]);
        }

        // === Upload sementara ===
        $path = $file->storeAs('temp_ocr', uniqid() . '.' . $file->getClientOriginalExtension(), 'public');

        try {
            $response = Http::withToken($token)
                ->attach('file', file_get_contents(storage_path('app/public/' . $path)), basename($path))
                ->put($url);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'http_code' => $response->status(),
                    'reason' => $response->reason(),
                    'body' => $response->body(),
                ], $response->status());
            }

            $ocrData = $response->json();

            Biodata::where('user_id', auth()->id())->update([
                'ocr_ktp'    => json_encode($ocrData, JSON_PRETTY_PRINT),
                'ocr_ktp_at' => now(),
            ]);

            file_put_contents($cacheFile, json_encode($ocrData, JSON_PRETTY_PRINT));

            return response()->json([
                'success' => true,
                'cached'  => false,
                'data'    => $ocrData,
            ]);
        } catch (\Throwable $e) {

            Log::info('OCR KTP Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat OCR.',
            ]);
        } finally {
            Storage::disk('public')->delete($path);
        }
    }
}
