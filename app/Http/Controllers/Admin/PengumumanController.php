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
            $path = public_path('thumbnail');
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $fileName = date('Ymd') . '-' . $file->getClientOriginalName();
            $file->move($path, $fileName);
        }

        Pengumuman::create([
            'pengumuman' => $request->pengumuman,
            'thumbnail' => $fileName,
            'keterangan' => $request->keterangan
        ]);

        Alert::success('Berhasil', 'Pengumuman berhasil dibuat');
        return redirect()->route('pengumumans.index');
    }

    public function edit($id)
    {
        $pengumuman = Pengumuman::where('id', $id)->first();
        return view('admin.pengumuman.edit', compact('pengumuman'));
    }

    public function update(Request $request, $id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->pengumuman = $request->pengumuman;
        $pengumuman->keterangan = $request->keterangan;

        if ($request->hasFile('thumbnail')) {
            $oldFile = public_path('thumbnail/' . $pengumuman->thumbnail);
            if ($pengumuman->thumbnail && file_exists($oldFile)) {
                unlink($oldFile);
            }

            $file = $request->file('thumbnail');
            $path = public_path('thumbnail');
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $fileName = date('Ymd') . '-' . $file->getClientOriginalName();
            $file->move($path, $fileName);
            $pengumuman->thumbnail = $fileName;
        }

        $pengumuman->save();
        Alert::success('Berhasil', 'Pengumuman berhasil diperbarui');
        return redirect()->route('pengumumans.index');
    }

    public function destroy($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        if ($pengumuman->thumbnail && file_exists($pengumuman->thumbnail)) {
            unlink($pengumuman->thumbnail);
        }
        $pengumuman->delete();

        Alert::success('Berhasil', 'Pengumuman berhasil dihapus');
        return redirect()->route('pengumumans.index');
    }
}
