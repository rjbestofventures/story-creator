<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Notifications\NonVbpSignupNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * The Non-VBP sign-up page. The plan picked on the signup page rides along
     * so it can be saved on the new account.
     */
    public function create(Request $request): Response
    {
        $plan = $request->query('plan');

        return Inertia::render('Auth/Register', [
            'plan' => in_array($plan, User::RETAIL_PLANS, true) ? $plan : null,
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => "{$data['first_name']} {$data['last_name']}",
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'signup_plan' => $data['plan'] ?? null,
        ]);

        $user->assignRole('user');
        $user->markPasswordSet();

        Auth::login($user);
        $user->recordLogin();

        NonVbpSignupNotification::sendFor($user);
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
