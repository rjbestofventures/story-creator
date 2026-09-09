<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'tier' => ['sometimes', 'string', Rule::in(['user', 'admin'])],
            'trial_allowance' => ['sometimes', 'integer', 'min:0', 'max:20'],
        ]);

        // Allowance above zero is what puts an account into trial; omitting the
        // param keeps the previous behaviour of creating a plain account.
        $allowance = (int) ($validated['trial_allowance'] ?? 0);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)),
            'is_trial' => $allowance > 0,
            'trial_allowance' => $allowance,
        ]);

        $user->syncRoles([$validated['tier'] ?? 'user']);

        // The account holds an unusable password until the member sets their own
        // through this reset link.
        $user->notify(new AccountCreatedNotification(Password::createToken($user)));

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tier' => $user->roles->first()?->name ?? 'user',
            'is_trial' => $user->is_trial,
            'trial_allowance' => $user->trial_allowance,
        ], 201);
    }
}
