<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class CekVerifikasiEmail
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->status_akun != 1) {
            Alert::error('Cek Email', 'Silakan verifikasi email terlebih dahulu.');
            return redirect()->back();
        }

        return $next($request);
    }
}
