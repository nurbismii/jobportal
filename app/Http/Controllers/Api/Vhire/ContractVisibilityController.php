<?php

namespace App\Http\Controllers\Api\Vhire;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vhire\ContractVisibilityRequest;
use App\Models\VhirePkwtContract;
use App\Services\Vhire\PkwtContractService;
use Illuminate\Http\JsonResponse;

class ContractVisibilityController extends Controller
{
    public function update(ContractVisibilityRequest $request, VhirePkwtContract $contract, PkwtContractService $contracts): JsonResponse
    {
        $contract = $contracts->updateVisibility(
            $contract,
            (bool) $request->boolean('visible_in_vhire'),
            $request->input('hidden_reason'),
            'vhire_admin_api'
        );

        return response()->json([
            'message' => 'Visibility kontrak berhasil diperbarui.',
            'contract' => [
                'id' => $contract->id,
                'visible_in_vhire' => $contract->visible_in_vhire,
                'hidden_reason' => $contract->hidden_reason,
                'hidden_at' => optional($contract->hidden_at)->toIso8601String(),
            ],
        ]);
    }
}
