<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateSignPkwtContractRequest;
use App\Models\VhirePkwtContract;
use App\Services\Vhire\PkwtContractFileService;
use App\Services\Vhire\PkwtContractService;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class PkwtContractController extends Controller
{
    public function index(PkwtContractService $contracts)
    {
        $contracts = $contracts->visibleContractsForUser(Auth::user());

        return view('user.pkwt-contracts.index', compact('contracts'));
    }

    public function show(VhirePkwtContract $contract)
    {
        $this->authorizeCandidateContract($contract);

        return view('user.pkwt-contracts.show', compact('contract'));
    }

    public function download(VhirePkwtContract $contract, PkwtContractFileService $files)
    {
        $this->authorizeCandidateContract($contract);
        abort_if(blank($contract->contract_file_path) || blank($contract->contract_file_disk), 404);

        return $files->response(
            $contract->contract_file_disk,
            $contract->contract_file_path,
            $contract->contract_file_name,
            $contract->contract_file_mime
        );
    }

    public function sign(CandidateSignPkwtContractRequest $request, VhirePkwtContract $contract, PkwtContractService $contracts)
    {
        try {
            $contracts->signElectronically($contract, Auth::user(), $request->input('candidate_signature'));
            Alert::success('Berhasil', 'Kontrak PKWT 1 berhasil ditandatangani.');
        } catch (\InvalidArgumentException $e) {
            Alert::error('Gagal', $e->getMessage());
        }

        return redirect()->route('kontrak-pkwt.index');
    }

    private function authorizeCandidateContract(VhirePkwtContract $contract): void
    {
        $user = Auth::user();
        $noKtp = preg_replace('/\D+/', '', (string) ($user->no_ktp ?? optional($user->biodata)->no_ktp));

        abort_if($contract->no_ktp !== $noKtp || ! $contract->isVisibleForCandidate(), 404);
    }
}
