<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

/**
 * Verified business partners are vetted off-platform before their account is
 * created, so the email verification gate never applies to them — however the
 * account was made (admin panel, provision API, or promoted afterwards).
 */
class EnsureEmailIsVerifiedOrPartner extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if ($request->user()?->is_verified_partner) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
