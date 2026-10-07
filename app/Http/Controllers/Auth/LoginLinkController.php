<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginLinkController extends Controller
{
    private const DESTINATIONS = ['stories' => 'stories.index', 'profile' => 'profile.edit'];

    /**
     * Sign the member in from a welcome-email link. Signature and expiry are
     * enforced by the `signed` middleware; the hash ties the link to the
     * password it was sent with. Arriving here proves they own the inbox.
     */
    public function __invoke(Request $request, User $user, string $destination, string $hash): RedirectResponse
    {
        abort_unless(hash_equals($user->loginLinkHash(), $hash), 403);

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ]);
        }

        if (Auth::id() !== $user->id) {
            Auth::login($user);
            $request->session()->regenerate();
            $user->recordLogin();
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->route(self::DESTINATIONS[$destination]);
    }
}
