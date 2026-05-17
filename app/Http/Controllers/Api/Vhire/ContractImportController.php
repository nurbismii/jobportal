<?php

namespace App\Http\Controllers\Api\Vhire;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vhire\HrisContractImportRequest;
use App\Services\Vhire\PkwtContractService;
use Illuminate\Http\JsonResponse;

class ContractImportController extends Controller
{
    public function store(HrisContractImportRequest $request, PkwtContractService $contracts): JsonResponse
    {
        $contract = $contracts->importFromHris($request->validated());

        return response()->json([
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
}
