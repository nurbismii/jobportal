<?php

namespace App\Http\Middleware;

use App\Models\AssessmentLink;
use Closure;
use Illuminate\Http\Request;

class EnsurePublicAssessmentAccess
{
    public function handle(Request $request, Closure $next)
    {
        $link = AssessmentLink::query()->where('public_token', $request->route('token'))->firstOrFail();
        abort_unless($link->isAccessibleAt(now('Asia/Makassar')), 404);
        abort_unless(session()->has('assessment_link_access.'.$link->id), 403);

        $request->attributes->set('publicAssessmentLink', $link);

        return $next($request);
    }
}
