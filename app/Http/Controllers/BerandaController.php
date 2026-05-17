<?php

namespace App\Http\Controllers;

use App\Models\Hris\Departemen;
use App\Models\Hris\Employee;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Pengumuman;
use App\Models\User;
use App\Services\Vhire\PkwtContractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class BerandaController extends Controller
{
    public function index()
    {
        $VDNI = 1;
        $VDNIP = 2;

        $step = calcutaionStep(auth()->user()->biodata ?? null);

        $lowongans = Lowongan::select('*')
            ->selectRaw("IF(tanggal_berakhir < ?, 'Kadaluwarsa', 'Aktif') as status_lowongan", [Carbon::now()])
            ->where('tanggal_mulai', '<=', Carbon::now()) // hanya yang sudah mulai
            ->having('status_lowongan', '=', 'Aktif')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $pengumumans = Pengumuman::orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $count_karyawan = Employee::where('status_resign', 'Aktif')->whereIn('area_kerja', ['VDNI', 'VDNIP'])->count();
        $count_user = User::count();
        $count_departemen = Departemen::whereIn('perusahaan_id', [$VDNI, $VDNIP])->count();
        $showPkwtContractFeature = $this->userHasPkwtSigningProcess();
        $visiblePkwtContractsCount = 0;

        if ($showPkwtContractFeature && Schema::hasTable('vhire_pkwt_contracts')) {
            $visiblePkwtContractsCount = app(PkwtContractService::class)
                ->visibleContractsForUser(auth()->user())
                ->count();
        }

        return view('beranda', compact(
            'lowongans',
            'pengumumans',
            'count_karyawan',
            'count_user',
            'count_departemen',
            'step',
            'showPkwtContractFeature',
            'visiblePkwtContractsCount'
        ));
    }

    private function userHasPkwtSigningProcess(): bool
    {
        if (! auth()->check() || auth()->user()->role !== 'user') {
            return false;
        }

        $biodata = auth()->user()->biodata;

        if (! $biodata) {
            return false;
        }

        return Lamaran::where('biodata_id', $biodata->id)
            ->whereRaw('LOWER(TRIM(status_proses)) in (?, ?, ?)', [
                'tanda tangan kontrak',
                'proses tanda tangan kontrak',
                'proses_tanda_tangan_kontrak',
            ])
            ->exists();
    }
}
