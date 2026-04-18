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
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->status_akun != 1) {
            $email = Auth::user()->email;

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Alert::warning('Verifikasi email', 'Silakan verifikasi email terlebih dahulu atau kirim ulang email verifikasi.');

            return redirect()->route('verification.notice.public', [
                'email' => $email,
            ]);
        }

        return $next($request);
    }
}
