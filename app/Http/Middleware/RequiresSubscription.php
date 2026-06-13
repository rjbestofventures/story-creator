<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin() || $user?->hasActiveSubscription()) {
            return $next($request);
        }

        return redirect()->route('billing.plans')
            ->with('notice', 'Please select a plan to start creating your story library.');
    }
}
