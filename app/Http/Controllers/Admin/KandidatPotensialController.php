<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\KemampuanPengalamanImport;
use App\Models\Biodata;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;

class KandidatPotensialController extends Controller
{
    public function index()
    {
        $kandidat_potensial = Biodata::with('user', 'getRiwayatLamaran', 'getLatestRiwayatLamaran')->where('status_potensial', '=', 1)->get();
        $title = 'Hapus Kandidat Potensial!';
        $text = "Kamu yakin ingin menghapus kandidat ini?";
        confirmDelete($title, $text);

        return view('admin.kandidat-potensial.index', compact('kandidat_potensial'))->with('no');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx|max:2048',
        ]);

        try {
            $import = new KemampuanPengalamanImport;
            Excel::import($import, $request->file('file'));

            if ($import->failures()->isNotEmpty()) {
                return back()->with('errors_import', $import->failures());
            }

            Alert::success('Berhasil', 'Data berhasil diupdate!');
            return back();
        } catch (\Exception $e) {
            Alert::error('Gagal', $e->getMessage());
            return back();
        }
    }
}
