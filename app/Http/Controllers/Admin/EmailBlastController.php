<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailBlastLog;

class EmailBlastController extends Controller
{
    public function index()
    {
        $datas = EmailBlastLog::with('user')->orderBy('created_at', 'desc')->get();

        return view('admin.email-blast-log.index', compact('datas'));
    }
}
