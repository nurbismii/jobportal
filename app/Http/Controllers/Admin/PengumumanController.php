<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PengumumanController extends Controller
{
    public function index()
    {
        $title = 'Hapus Lowongan!';
        $text = "Kamu yakin ingin menghapus lowongan ini?";
        confirmDelete($title, $text);

        $pengumumans = Pengumuman::orderBy('id', 'desc')->get();

        return view('admin.pengumuman.index', compact('pengumumans'))->with('no');
    }

    public function create()
    {
        return view('admin.pengumuman.create');
    }

    public function store(Request $request)
    {

        if ($request->hasFile('thumbnail')) {

            $file = $request->file('thumbnail');
            $path = public_path('pengumuman' . '/' . date('Ymd'));
            $fileName = $file->getClientOriginalName();
            $savePath = $path . '/' . $fileName;
        }

        Pengumuman::create([
            'pengumuman' => $request->pengumuman,
            'thumbnail' => $savePath ?? null,
            'keterangan' => $request->keterangan
        ]);

        Alert::success('Berhasil', 'Pengumuman berhasil dibuat');
        return redirect()->route('pengumuman.index');
    }

    public function edit($id)
    {
        $pengumuman = Pengumuman::where('id', $id)->first();
        return view('admin.pengumuman.edit', compact('pengumuman'));
    }
}
