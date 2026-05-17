<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\SyncContractSignatureStatusToHris;
use App\Jobs\SyncOnboardingCandidateToHris;
use App\Http\Controllers\Controller;
use App\Models\VhireOnboardingCandidate;
use App\Models\VhirePkwtContract;
use App\Services\Vhire\PkwtContractFileService;
use App\Services\Vhire\PkwtContractService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PkwtContractController extends Controller
{
    public function index(Request $request)
    {
        $contracts = VhirePkwtContract::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->search . '%';
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nama', 'like', $search)
                        ->orWhere('candidate_code', 'like', $search)
                        ->orWhere('kode_kontrak', 'like', $search)
                        ->orWhere('no_pkwt', 'like', $search)
                        ->orWhere('employee_nik', 'like', $search);
                });
            })
            ->when($request->filled('signing_method'), function ($query) use ($request) {
                $query->where('signing_method', $request->signing_method);
            })
            ->when($request->filled('signature_status'), function ($query) use ($request) {
                $query->where('signature_status', $request->signature_status);
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        $failedOnboardingCandidates = VhireOnboardingCandidate::where('sync_status', 'failed_sync')
            ->orderByDesc('last_sync_attempt_at')
            ->limit(20)
            ->get();

        return view('admin.pkwt-contracts.index', compact('contracts', 'failedOnboardingCandidates'));
    }

    public function updateVisibility(Request $request, VhirePkwtContract $contract, PkwtContractService $contracts)
    {
        $request->validate([
            'visible_in_vhire' => ['required', 'boolean'],
            'hidden_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $contracts->updateVisibility(
                $contract,
                (bool) $request->boolean('visible_in_vhire'),
                $request->input('hidden_reason'),
                'admin'
            );
            Alert::success('Berhasil', 'Visibility kontrak diperbarui.');
        } catch (\InvalidArgumentException $e) {
            Alert::error('Gagal', $e->getMessage());
        }

        return back();
    }

    public function retrySignatureSync(VhirePkwtContract $contract)
    {
        SyncContractSignatureStatusToHris::dispatch($contract->id)
            ->onQueue((string) config('recruitment.hris_api.queue', 'default'));

        Alert::success('Berhasil', 'Retry sync status tanda tangan dikirim ke queue.');

        return back();
    }

    public function retryOnboardingSync(VhireOnboardingCandidate $candidate)
    {
        SyncOnboardingCandidateToHris::dispatch($candidate->id)
            ->onQueue((string) config('recruitment.hris_api.queue', 'default'));

        Alert::success('Berhasil', 'Retry sync onboarding dikirim ke queue.');

        return back();
    }

    public function download(VhirePkwtContract $contract, PkwtContractFileService $files)
    {
        abort_if(blank($contract->contract_file_path) || blank($contract->contract_file_disk), 404);

        return $files->response(
            $contract->contract_file_disk,
            $contract->contract_file_path,
            $contract->contract_file_name,
            $contract->contract_file_mime
        );
    }
}
