<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresCredits
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Trial members hold no credits by definition — locks, not the credit
        // balance, are what gate them.
        if ($user?->isAdmin() || $user?->is_trial || ($user && $user->credits > 0)) {
            return $next($request);
        }

        return redirect()->route('shop.index')
            ->with('notice', 'Buy credits to start creating your story library.');
    }
}
