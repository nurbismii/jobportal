<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\RiwayatProsesLamaran;
use Exception;
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
        try {
            $lamaran = Lamaran::with('lowongan', 'biodata')->where('id', $id)->first();
            $riwayat_proses = RiwayatProsesLamaran::where('lamaran_id', $lamaran->id)->where('status_lolos', null)->orderBy('created_at', 'desc')->get();

            return view('user.lamaran.show', compact('lamaran', 'riwayat_proses'));
        } catch (Exception $e) {
            Alert::error('Opps!', 'Terjadi kesalahan saat mengambil data lamaran.');
            return redirect()->route('lamaran.index');
        }
    }
}
