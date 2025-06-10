<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\RiwayatProsesLamaran;
use Illuminate\Http\Request;

class LamaranController extends Controller
{
    public function index()
    {
        $biodata = Biodata::where('user_id', auth()->id())->first();

        $lamarans = Lamaran::with('lowongan', 'biodata')->where('biodata_id', $biodata->id)->orderBy('id', 'desc')->get();

        return view('user.lamaran.index', compact('lamarans'));
    }

    public function show($id)
    {

        $biodata = Biodata::where('user_id', auth()->id())->first();

        $lamaran = Lamaran::with('lowongan', 'biodata')->where('biodata_id', $biodata->id)->orderBy('id', 'desc')->first();

        $riwayat_proses = RiwayatProsesLamaran::where('lamaran_id', $lamaran->id)->get();

        return view('user.lamaran.show', compact('lamaran', 'riwayat_proses'));
    }
}
