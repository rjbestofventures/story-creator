<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLandingLock
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteSetting::get('landing_lock_enabled')) {
            return $next($request);
        }

        if ($request->user()) {
            return $next($request);
        }

        if ($request->session()->get('landing_unlocked')) {
            return $next($request);
        }

        return redirect()->route('landing.unlock');
    }
}
