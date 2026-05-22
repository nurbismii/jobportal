<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\SyncContractSignatureStatusToHris;
use App\Http\Controllers\Controller;
use App\Models\VhirePkwtContract;
use App\Services\Vhire\PkwtContractFileService;
use App\Services\Vhire\PkwtContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
            ->when($request->filled('match_status'), function ($query) use ($request) {
                if ($request->match_status === 'hidden') {
                    $query->where('visible_in_vhire', false);
                } else {
                    $query->where('match_status', $request->match_status);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.pkwt-contracts.index', compact('contracts'));
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

    public function bulkUpdateVisibility(Request $request, PkwtContractService $contracts)
    {
        $validator = Validator::make($request->all(), [
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer', 'distinct', 'exists:vhire_pkwt_contracts,id'],
            'bulk_action' => ['required', 'in:show,hide'],
            'hidden_reason' => ['nullable', 'string', 'max:255'],
        ], [
            'selected_ids.required' => 'Pilih minimal satu kontrak PKWT 1.',
            'selected_ids.min' => 'Pilih minimal satu kontrak PKWT 1.',
            'selected_ids.*.exists' => 'Ada kontrak PKWT 1 yang tidak ditemukan.',
            'bulk_action.in' => 'Aksi massal tidak valid.',
        ]);

        if ($validator->fails()) {
            Alert::error('Gagal', $validator->errors()->first());

            return back()->withInput();
        }

        $validated = $validator->validated();
        $visible = $validated['bulk_action'] === 'show';
        $summary = $contracts->bulkUpdateVisibility(
            $validated['selected_ids'],
            $visible,
            $validated['hidden_reason'] ?? null,
            'admin'
        );

        $actionLabel = $visible ? 'ditampilkan' : 'disembunyikan';
        $message = $summary['updated'] . ' kontrak PKWT 1 berhasil ' . $actionLabel . '.';

        if ($summary['unchanged'] > 0) {
            $message .= ' ' . $summary['unchanged'] . ' kontrak tidak berubah.';
        }

        if ($summary['skipped_employee'] > 0) {
            $message .= ' ' . $summary['skipped_employee'] . ' kontrak dilewati karena kandidat sudah memiliki NIK HRIS.';
        }

        if ($summary['updated'] > 0) {
            Alert::success('Berhasil', $message);
        } else {
            Alert::error('Tidak ada perubahan', $message);
        }

        return back();
    }

    public function rematch(VhirePkwtContract $contract, PkwtContractService $contracts)
    {
        try {
            $contracts->rematch($contract, optional(Auth::user())->id);
            Alert::success('Berhasil', 'Kontrak berhasil ditautkan ke kandidat berdasarkan No KTP.');
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
