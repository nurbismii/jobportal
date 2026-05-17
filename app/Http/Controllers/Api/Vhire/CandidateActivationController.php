<?php

namespace App\Http\Controllers\Api\Vhire;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vhire\HrisCandidateActivatedRequest;
use App\Services\Vhire\PkwtContractService;
use Illuminate\Http\JsonResponse;

class CandidateActivationController extends Controller
{
    public function store(HrisCandidateActivatedRequest $request, PkwtContractService $contracts): JsonResponse
    {
        $payload = $request->validated();
        $vhireCandidateId = $request->route('vhire_candidate_id');

        if ($vhireCandidateId) {
            $payload['vhire_candidate_id'] = $vhireCandidateId;
        }

        $affected = $contracts->markActivated($payload);

        return response()->json([
            'message' => 'Aktivasi kandidat HRIS diterima V-Hire.',
            'hidden_contracts' => $affected,
        ]);
    }
}
