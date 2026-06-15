<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class SyaratKetentuanController extends Controller
{
    public function approved(Request $request)
    {
        $biodata = Biodata::where('user_id', $request->user()->id)->first();

        if (! $biodata || blank($biodata->status_pernyataan)) {
            Alert::warning('Peringatan', 'Anda belum menyetujui syarat dan ketentuan rekrutmen.');

            return redirect()->to(route('biodata.index') . '#step6');
        }

        $approvedAt = $biodata->status_pernyataan_disetujui_pada ?: $biodata->updated_at;

        return view('user.syarat-ketentuan.approved', compact('biodata', 'approvedAt'));
    }
}
