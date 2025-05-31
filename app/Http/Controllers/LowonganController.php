<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LowonganController extends Controller
{
    public function index()
    {
        return view('user.lowongan-kerja.index');
    }
}
