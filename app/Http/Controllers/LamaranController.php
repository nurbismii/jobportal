<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\RiwayatProsesLamaran;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class LamaranController extends Controller
{
    public function index()
    {
        $biodata = Biodata::where('user_id', auth()->id())->first();

        if ($biodata) {
            $lamarans = Lamaran::with('lowongan', 'biodata')->where('biodata_id', $biodata->id)->orderBy('id', 'desc')->get();
            return view('user.lamaran.index', compact('lamarans'));
        }
        Alert::warning('Opss!', 'Silakan lakukan pelamaran kerja terlebih dahulu');
        return redirect()->route('biodata.index');
    }

    public function show($id)
    {

        $biodata = Biodata::where('user_id', auth()->id())->first();

        $lamaran = Lamaran::with('lowongan', 'biodata')->where('biodata_id', $biodata->id)->orderBy('id', 'desc')->first();

        $riwayat_proses = RiwayatProsesLamaran::where('lamaran_id', $lamaran->id)->get();

        return view('user.lamaran.show', compact('lamaran', 'riwayat_proses'));
    }
}
