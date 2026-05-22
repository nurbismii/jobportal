<?php

namespace App\Http\Controllers\Api\Vhire;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vhire\HrisContractImportRequest;
use App\Services\Vhire\PkwtContractService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ContractImportController extends Controller
{
    public function store(HrisContractImportRequest $request, PkwtContractService $contracts): JsonResponse
    {
        try {
            $contract = $contracts->importFromHris($request->validated());
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (QueryException $exception) {
            return $this->failureResponse($request, $exception, 'database');
        } catch (Throwable $exception) {
            return $this->failureResponse($request, $exception, 'server');
        }

        return response()->json([
            'success' => true,
            'message' => 'Kontrak PKWT 1 berhasil disimpan di V-Hire.',
            'contract' => [
                'id' => $contract->id,
                'hris_contract_id' => $contract->hris_contract_id,
                'vhire_candidate_id' => $contract->vhire_candidate_id,
                'candidate_code' => $contract->candidate_code,
                'no_ktp_masked' => $contract->masked_no_ktp,
                'kode_kontrak' => $contract->kode_kontrak,
                'no_pkwt' => $contract->no_pkwt,
                'signature_status' => $contract->signature_status,
                'signing_method' => $contract->signing_method,
                'visible_in_vhire' => $contract->visible_in_vhire,
                'match_status' => $contract->match_status,
                'matched_biodata_id' => $contract->matched_biodata_id,
                'matched_lamaran_id' => $contract->matched_lamaran_id,
            ],
        ]);
    }

    private function failureResponse(HrisContractImportRequest $request, Throwable $exception, string $type): JsonResponse
    {
        $errorId = 'VHIRE-PKWT-' . (string) Str::uuid();
        $payload = $request->validated();

        Log::error('VHIRE_PKWT_CONTRACT_IMPORT_FAILED', [
            'error_id' => $errorId,
            'type' => $type,
            'exception' => get_class($exception),
            'message' => $this->safeExceptionMessage($exception),
            'hris_contract_id' => $payload['hris_contract_id'] ?? null,
            'vhire_candidate_id' => $payload['vhire_candidate_id'] ?? null,
            'candidate_code' => $payload['candidate_code'] ?? null,
            'no_ktp_masked' => isset($payload['no_ktp']) ? mask_no_ktp($payload['no_ktp']) : null,
            'kode_kontrak' => $payload['kode_kontrak'] ?? null,
            'no_pkwt' => $payload['no_pkwt'] ?? null,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Import kontrak PKWT 1 gagal diproses di V-Hire.',
            'error_id' => $errorId,
            'error_detail' => $this->publicErrorDetail($exception, $type),
        ], 500);
    }

    private function publicErrorDetail(Throwable $exception, string $type): string
    {
        if ($exception instanceof QueryException) {
            $sqlState = $exception->errorInfo[0] ?? null;
            $driverCode = $exception->errorInfo[1] ?? null;

            if ($sqlState === '42S22') {
                return 'Struktur tabel V-Hire belum sesuai dengan kode terbaru. Jalankan migration V-Hire, lalu retry sync dari HRIS.';
            }

            if ($sqlState === '42S02') {
                return 'Tabel integrasi PKWT 1 belum tersedia di database V-Hire. Jalankan migration V-Hire, lalu retry sync dari HRIS.';
            }

            if ($sqlState === '23000') {
                return 'Data kontrak bentrok dengan aturan unik database V-Hire. Periksa hris_contract_id, kode_kontrak, no_pkwt, atau candidate_code.';
            }

            return trim(sprintf(
                'Database V-Hire gagal memproses kontrak. SQLSTATE: %s%s.',
                $sqlState ?: '-',
                $driverCode ? ', code: ' . $driverCode : ''
            ));
        }

        return $type === 'server'
            ? 'Terjadi error internal di V-Hire. Cek log V-Hire menggunakan error_id pada response ini.'
            : 'Import kontrak gagal diproses di V-Hire.';
    }

    private function safeExceptionMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $message = preg_replace('/\b\d{16}\b/', '[masked-ktp]', $message);
        $message = preg_replace('/data:(?:application|image)\/[A-Za-z0-9.+-]+;base64,[A-Za-z0-9+\/=\r\n]+/i', '[base64-file]', $message);

        return Str::limit((string) $message, 2000);
    }
}
