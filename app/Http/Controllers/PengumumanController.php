<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;

class PengumumanController extends Controller
{
    public function index()
    {
        $pengumumans = Pengumuman::orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('user.pengumuman.index', compact('pengumumans'));
    }

    public function show($id)
    {
        $pengumuman = Pengumuman::where('id', $id)->first();
        $pengumumans = Pengumuman::orderBy('id', 'desc')->get();

        return view('user.pengumuman.show', compact('pengumuman', 'pengumumans'));
    }
}
