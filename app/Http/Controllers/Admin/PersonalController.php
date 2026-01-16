<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;

class PersonalController extends Controller
{
    public function index()
    {
        $biodata = Biodata::with('user')->whereHas('user', function ($query) {
            $query->where('status_akun', 1);
        })->get();

        return view('admin.personal-file.index', compact('biodata'))->with('no');
    }
}
