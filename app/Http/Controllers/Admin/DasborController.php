<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lowongan;
use App\Models\Pengumuman;
use App\Models\User;

class DasborController extends Controller
{
    public function index()
    {
        $count_user = User::where('status_akun', '=', '1')->count();
        $count_lowongan_aktif = Lowongan::where('tanggal_berakhir', '>', date('Y-m-d H:i:s'))->count();
        $count_lowongan_tidak_aktif = Lowongan::where('tanggal_berakhir', '<', date('Y-m-d H:i:s'))->count();
        $count_pengumuman = Pengumuman::count();

        return view('admin.dasbor.index', compact('count_user', 'count_lowongan_aktif', 'count_lowongan_tidak_aktif', 'count_pengumuman'));
    }
}
