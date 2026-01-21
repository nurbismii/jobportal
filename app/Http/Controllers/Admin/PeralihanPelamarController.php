<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PeralihanPelamarController extends Controller
{
    public function index()
    {
        $biodatas = Biodata::with('user', 'getLatestRiwayatLamaran')->get(['id', 'user_id', 'no_ktp']);

        return view('admin.peralihan-pelamar.index', compact('biodatas'))->with('no');
    }

    public function edit($id)
    {
        $biodata = Biodata::with('user', 'getLatestRiwayatLamaran')->where('id', $id)->first(['id', 'user_id', 'no_ktp']);

        $lowongans = Lowongan::select('*')
            ->selectRaw("IF(tanggal_berakhir < ?, 'Kadaluwarsa', 'Aktif') as status_lowongan", [Carbon::now()])
            ->where('tanggal_mulai', '<=', Carbon::now()) // hanya yang sudah mulai
            ->having('status_lowongan', '=', 'Aktif')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.peralihan-pelamar.edit', compact('biodata', 'lowongans'))->with('no');
    }

    public function update(Request $request, $id)
    {
        $lamaran = Lamaran::find($id);

        if ($lamaran) {
            $lamaran->where('loker_id', $lamaran->loker_id)->where('biodata_id', $lamaran->biodata_id)->update([
                'loker_id' => $request->loker_id,
                'loker_id_lama' => $request->loker_id_lama
            ]);

            Alert::success('Berhasil', 'Data pelamar berhasil dialihkan');
            return back();
        }

        Alert::error('Gagal', 'Data tidak berhasil diperbarui');
        return back();
    }
}
