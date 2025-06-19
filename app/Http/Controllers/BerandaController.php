<?php

namespace App\Http\Controllers;

use App\Models\Hris\Departemen;
use App\Models\Hris\Employee;
use App\Models\Lowongan;
use App\Models\Pengumuman;
use App\Models\User;
use Carbon\Carbon;

class BerandaController extends Controller
{
    public function index()
    {
        $VDNI = 1;
        $VDNIP = 2;

        $lowongans = Lowongan::select('*')
            ->selectRaw("IF(tanggal_berakhir < ?, 'Kadaluwarsa', 'Aktif') as status_lowongan", [Carbon::today()->toDateString()])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $pengumumans = Pengumuman::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $count_karyawan = Employee::where('status_resign', 'Aktif')->whereIn('area_kerja', ['VDNI', 'VDNIP'])->count();
        $count_user = User::count();
        $count_departemen = Departemen::whereIn('perusahaan_id', [$VDNI, $VDNIP])->count();

        return view('beranda', compact('lowongans', 'pengumumans', 'count_karyawan', 'count_user', 'count_departemen'));
    }
}
