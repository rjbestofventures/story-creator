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

        if ($user?->isAdmin() || ($user && $user->credits > 0)) {
            return $next($request);
        }

        return redirect()->route('shop.index')
            ->with('notice', 'Buy credits to start creating your story library.');
    }
}
