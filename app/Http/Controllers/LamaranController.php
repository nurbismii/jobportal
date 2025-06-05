<?php

namespace App\Http\Controllers;

use App\Models\Lamaran;
use Illuminate\Http\Request;

class LamaranController extends Controller
{
    public function index()
    {
        $lamaran = Lamaran::with('lowongan', 'biodata')->where('user_id', auth()->id())->orderBy('id', 'desc')->get();

        return view('user.lamaran.index', compact('lamaran'));
    }

}
