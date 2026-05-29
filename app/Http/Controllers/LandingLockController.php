<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingLockController extends Controller
{
    public function show(): \Inertia\Response|RedirectResponse
    {
        if (! SiteSetting::get('landing_lock_enabled')) {
            return redirect()->route('welcome');
        }

        return Inertia::render('LandingLock');
    }

    public function unlock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => 'required|string',
        ]);

        $correct = SiteSetting::get('landing_lock_password', '');

        if (! $correct || $data['password'] !== $correct) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $request->session()->put('landing_unlocked', true);

        return redirect()->route('welcome');
    }
}
