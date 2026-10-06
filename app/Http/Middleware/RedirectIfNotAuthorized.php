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

        if ($user->role === 'user') {
            return redirect('/');
        }

        abort_unless($user->isInternalUser() && (int) $user->status_akun === 1, 403, 'Akun tidak memiliki akses aktif.');

        if ($user->role !== 'admin') {
            $requirement = \App\Support\AdminAccess::requirement($request->route());
            // The landing page contains no module data and is available to all internal accounts.
            if (! $request->routeIs('internal-accounts.landing')) {
                abort_unless($requirement && $user->hasModulePermission(...$requirement), 403, 'Anda tidak memiliki izin untuk tindakan pada modul ini.');
            }
        }

        return $next($request);
    }
}
