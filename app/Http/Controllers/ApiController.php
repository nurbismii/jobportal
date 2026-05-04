<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Hris\Divisi;
use App\Models\Hris\Kabupaten;
use App\Models\Hris\Kecamatan;
use App\Models\Hris\Kelurahan;
use App\Services\EmploymentStatusRefreshService;
use App\Services\Ocr\KtpIdentityValidator;
use App\Services\Ocr\ParsedKtp;
use App\Services\Ocr\ParsedSimB2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class ApiController extends Controller
{
    protected string $text;

    private function validateOcrImage(Request $request, string $field, string $label)
    {
        return Validator::make(
            $request->all(),
            [
                $field => 'required|image|mimes:jpg,jpeg,png|max:2048',
            ],
            [
                'required' => ':attribute wajib diupload.',
                'image' => ':attribute harus berupa foto/gambar.',
                'mimes' => 'Format :attribute harus berupa jpg, jpeg, atau png.',
                'max' => 'Ukuran :attribute maksimal 2 MB.',
            ],
            [
                $field => $label,
            ]
        );
    }

    private function ktpValidationFailureResponse(array $parsedResult, ?Biodata $biodata)
    {
        $identityValidator = app(KtpIdentityValidator::class);
        $validation = $identityValidator->validateParsedResult($parsedResult, request()->user(), $biodata);

        if ($validation['valid']) {
            return null;
        }

        return $this->rejectKtpOcr($biodata, $validation['message'], $validation);
    }

    private function rejectKtpOcr(?Biodata $biodata, string $message, array $context = [], int $status = 422, bool $clearUploadedKtp = true)
    {
        $identityValidator = app(KtpIdentityValidator::class);
        $logContext = [
            'user_id' => auth()->id(),
            'reason' => $message,
        ];

        foreach (['ocr_nik', 'account_nik', 'biodata_nik'] as $key) {
            if (isset($context[$key])) {
                $logContext[$key] = $identityValidator->maskNik($context[$key]);
            }
        }

        if (isset($context['has_hris_history'])) {
            $logContext['has_hris_history'] = (bool) $context['has_hris_history'];
        }

        Log::warning('OCR KTP ditolak', $logContext);

        if ($clearUploadedKtp && $biodata && $biodata->ktp) {
            $filePath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->ktp);

            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            $biodata->forceFill([
                'ktp' => null,
                'ocr_ktp' => null,
                'ocr_ktp_at' => null,
            ])->save();
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'clear_file' => $clearUploadedKtp,
        ], $status);
    }

    private function refreshEmploymentStatusFromHris(): void
    {
        app(EmploymentStatusRefreshService::class)->refreshUser(
            auth()->user()->load('biodata')
        );
    }

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

    // OCR SPACE SIM B2 V2
    public function ocrSimB2(Request $request)
    {
        $validator = $this->validateOcrImage($request, 'sim_b_2', 'SIM B II');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $apiKey = config('services.ocr_space.key');
        $endpoint = config('services.ocr_space.endpoint', 'https://api.ocr.space/parse/image');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi OCR.space belum lengkap.',
            ], 500);
        }

        $file = $request->file('sim_b_2');

        $path = $file->storeAs('temp_ocr', uniqid() . '.' . $file->getClientOriginalExtension(), 'public');
        $fullPath = storage_path('app/public/' . $path);

        $compressedPath = storage_path('app/public/temp_ocr/compressed_' . basename($path));

        // 🔥 KOMPRES FILE ≤ 1 MB
        compressImageTo1MB($fullPath, $compressedPath);

        try {
            // Panggil API OCR
            $response = Http::timeout((int) config('services.ocr_space.timeout', 30))
                ->attach('file', file_get_contents($compressedPath), basename($compressedPath))
                ->post($endpoint, [
                    'apikey' => $apiKey,
                    'language' => 'eng',
                    'OCREngine' => (string) config('services.ocr_space.sim_b2_engine', '2'),
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
            $parsedSimB2 = new ParsedSimB2();
            $parsedResult = $parsedSimB2->parse($biodata, false);

            Biodata::where('user_id', auth()->id())->update([
                'ocr_sim_b2' => $text,
                'parsed_sim_b2' => $parsedResult['data'],
            ]);

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

    // OCR KTP AKSARAKAN
    public function ocrKtp(Request $request)
    {
        $biodata = Biodata::where('user_id', auth()->id())->first();

        $validator = $this->validateOcrImage($request, 'ktp', 'KTP');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $biodata) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi biodata terlebih dahulu sebelum membaca KTP.',
            ], 422);
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

            if (! is_array($cachedData)) {
                return $this->rejectKtpOcr($biodata, 'Cache OCR KTP tidak valid. Silakan unggah ulang KTP.');
            }

            if ($failureResponse = $this->ktpValidationFailureResponse($cachedData, $biodata)) {
                return $failureResponse;
            }

            Biodata::where('user_id', auth()->id())->update([
                'ocr_ktp'    => json_encode($cachedData, JSON_PRETTY_PRINT),
                'ocr_ktp_at' => now(),
            ]);

            $this->refreshEmploymentStatusFromHris();

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
                Log::warning('OCR KTP internal gagal', [
                    'user_id' => auth()->id(),
                    'http_code' => $response->status(),
                    'reason' => $response->reason(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'OCR KTP gagal diproses. Silakan coba lagi atau unggah foto KTP yang lebih jelas.',
                ], 502);
            }

            $ocrData = $response->json();

            if (! is_array($ocrData)) {
                return $this->rejectKtpOcr($biodata, 'Hasil OCR KTP tidak valid. Silakan unggah ulang KTP.');
            }

            if ($failureResponse = $this->ktpValidationFailureResponse($ocrData, $biodata)) {
                return $failureResponse;
            }

            Biodata::where('user_id', auth()->id())->update([
                'ocr_ktp'    => json_encode($ocrData, JSON_PRETTY_PRINT),
                'ocr_ktp_at' => now(),
            ]);

            file_put_contents($cacheFile, json_encode($ocrData, JSON_PRETTY_PRINT));

            $this->refreshEmploymentStatusFromHris();

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

    // OCR SPACE KTP v3
    public function ocrSpaceKtp(Request $request)
    {
        $biodata = Biodata::where('user_id', auth()->id())->first();
        $validator = $this->validateOcrImage($request, 'ktp', 'KTP');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $biodata) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi biodata terlebih dahulu sebelum membaca KTP.',
            ], 422);
        }

        $apiKey = config('services.ocr_space.key');
        $endpoint = config('services.ocr_space.endpoint', 'https://api.ocr.space/parse/image');

        if (empty($apiKey)) {
            return $this->rejectKtpOcr(
                $biodata,
                'Konfigurasi OCR.space belum lengkap.',
                [],
                500,
                false
            );
        }

        $file = $request->file('ktp');

        // ================== SIMPAN FILE ==================
        $path = $file->storeAs(
            'temp_ocr',
            uniqid() . '.' . $file->getClientOriginalExtension(),
            'public'
        );

        $fullPath = storage_path('app/public/' . $path);
        $compressedPath = storage_path('app/public/temp_ocr/compressed_' . basename($path));

        // ================== KOMPRES ≤ 1MB ==================
        compressImageTo1MB($fullPath, $compressedPath);

        try {
            // ================== OCR API ==================
            $response = Http::timeout((int) config('services.ocr_space.timeout', 30))
                ->attach(
                    'file',
                    file_get_contents($compressedPath),
                    basename($compressedPath)
                )
                ->post($endpoint, [
                    'apikey'             => $apiKey,
                    'language'           => 'eng',
                    'OCREngine'          => (string) config('services.ocr_space.ktp_engine', '3'),
                    'scale'              => 'true',
                    'detectOrientation'  => 'true',
                    'isOverlayRequired'  => 'false',
                ]);

            if (!$response->successful() || data_get($response->json(), 'IsErroredOnProcessing') === true) {
                Log::warning('OCR Space gagal, fallback ke OCR internal', [
                    'user_id' => auth()->id()
                ]);

                return $this->ocrKtp($request);
            }

            $ocrData   = $response->json();
            $parsedText = $ocrData['ParsedResults'][0]['ParsedText'] ?? '';

            if (empty($parsedText)) {
                Log::warning('OCR Space hasil kosong, fallback', [
                    'user_id' => auth()->id()
                ]);

                return $this->ocrKtp($request);
            }

            // ================== PARSER KTP ==================
            $parsedResult = null;

            if (!empty($parsedText)) {

                $parser = new ParsedKtp();
                $parsedResult = $parser->parse($parsedText);

                if ($failureResponse = $this->ktpValidationFailureResponse($parsedResult, $biodata)) {
                    return $failureResponse;
                }

                Biodata::where('user_id', auth()->id())->update([
                    'ocr_ktp' => json_encode($parsedResult, JSON_PRETTY_PRINT),
                    'ocr_ktp_at' => now(),
                ]);

                $this->refreshEmploymentStatusFromHris();
            }

            return response()->json([
                'success' => true,
                'cached'  => false,
                'data'  => $parsedResult,
            ]);
        } catch (\Throwable $e) {

            Log::error('OCR KTP Error', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan, coba beberapa saat lagi.'
            ]);
        } finally {
            Storage::disk('public')->delete([
                $path,
                'temp_ocr/' . basename($compressedPath)
            ]);
        }
    }
}
