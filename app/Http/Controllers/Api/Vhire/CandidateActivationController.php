<?php

namespace App\Http\Controllers\Api\Vhire;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vhire\HrisCandidateActivatedRequest;
use App\Services\Vhire\PkwtContractService;
use Illuminate\Http\JsonResponse;

class CandidateActivationController extends Controller
{
    public function store(string $vhireCandidateId, HrisCandidateActivatedRequest $request, PkwtContractService $contracts): JsonResponse
    {
        $affected = $contracts->markActivated(array_merge($request->validated(), [
            'vhire_candidate_id' => $vhireCandidateId,
        ]));

        return response()->json([
            'message' => 'Aktivasi kandidat HRIS diterima V-Hire.',
            'hidden_contracts' => $affected,
        ]);
    }
}
