<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\User;
use RealRashid\SweetAlert\Facades\Alert;

class SyaratKetentuanController extends Controller
{
    public function show(User $pengguna)
    {
        $biodata = Biodata::where('user_id', $pengguna->id)->first();

        if (! $biodata || blank($biodata->status_pernyataan)) {
            Alert::warning('Peringatan', 'Pengguna ini belum menyetujui syarat dan ketentuan rekrutmen.');

            return redirect()->route('pengguna.show', $pengguna->id);
        }

        $approvedAt = $biodata->status_pernyataan_disetujui_pada ?: $biodata->updated_at;
        $printedAt = now();

        return view('admin.syarat-ketentuan.show', compact('pengguna', 'biodata', 'approvedAt', 'printedAt'));
    }
}
