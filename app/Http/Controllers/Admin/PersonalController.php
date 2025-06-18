<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;

class PersonalController extends Controller
{
    public function index()
    {
        $biodata = Biodata::with('user')->get();

        return view('admin.personal-file.index', compact('biodata'))->with('no');
    }
}
