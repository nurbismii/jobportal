<?php

namespace App\Http\Controllers;

use App\Models\Hris\Divisi;
use App\Models\Hris\Kabupaten;
use App\Models\Hris\Kecamatan;
use App\Models\Hris\Kelurahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        try {
            // Panggil API OCR
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($fullPath), basename($fullPath))
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

            // Gunakan parser
            $parsedResult = app()->call([new \App\Http\Controllers\LowonganController, 'parseSimB2'], ['biodata' => $biodata, 'save' => false]);

            return response()->json([
                'success' => true,
                'data' => $parsedResult['data'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat OCR.' . $e]);
        } finally {
            \Storage::disk('public')->delete($path);
        }
    }

    public function ocrKtp(Request $request)
    {
        if (!$request->hasFile('ktp')) {
            return response()->json(['success' => false, 'message' => 'File KTP tidak ditemukan.']);
        }

        $file = $request->file('ktp');
        $path = $file->storeAs('temp_ocr', uniqid() . '.' . $file->getClientOriginalExtension(), 'public');
        $fullPath = storage_path('app/public/' . $path);

        try {
            // Panggil API OCR
            $url = rtrim(config('services.ocr.link'), '/') . '/' . ltrim(config('services.ocr.type'), '/');

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                return back();
            }

            $fileContent = @file_get_contents($fullPath); // gunakan @ untuk suppress warning

            if ($fileContent === false) {
                return redirect()->to(route('biodata.index') . '#step5');
            }

            $response = Http::withToken(config('services.ocr.token'))
                ->withHeaders([
                    'Authentication' => 'bearer ' . config('services.ocr.token'),
                ])
                ->attach('file', file_get_contents($fullPath), basename($fullPath))
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

            $ocrData = $response->json();

            return response()->json([
                'success' => true,
                'data' => $ocrData,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat OCR.' . $e]);
        } finally {
            \Storage::disk('public')->delete($path);
        }
    }
}
