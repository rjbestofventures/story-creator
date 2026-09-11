<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProvisionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('app.provision_api_token');

        if (! $token || $request->bearerToken() !== $token) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
