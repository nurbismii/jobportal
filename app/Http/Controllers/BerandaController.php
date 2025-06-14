<?php

namespace App\Http\Controllers;

use App\Models\Lowongan;
use App\Models\Pengumuman;

class BerandaController extends Controller
{
    public function index()
    {
        $lowongans = Lowongan::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $pengumumans = Pengumuman::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('beranda', compact('lowongans', 'pengumumans'));
    }
}
