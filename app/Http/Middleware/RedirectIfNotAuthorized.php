<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotAuthorized
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Contoh: redirect berdasarkan role
        if ($user->role === 'admin') {
            return $next($request);
        } elseif ($user->role === 'user') {
            return redirect('/');
        }

        // Default jika role tidak dikenali
        abort(403, 'Akses tidak diizinkan.');
    }
}
