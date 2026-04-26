<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateInternalApi
{
    public function handle(Request $request, Closure $next)
    {
        $configuredToken = (string) config('recruitment.internal_api.token');
        $providedToken = (string) $request->bearerToken();

        if ($configuredToken === '' || $providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
