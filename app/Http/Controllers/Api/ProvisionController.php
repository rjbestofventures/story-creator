<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditPack;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProvisionController extends Controller
{
    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'pack' => ['sometimes', 'string', Rule::exists('credit_packs', 'slug')],
            'trial' => ['sometimes', 'boolean'],
        ]);

        $trial = (bool) ($validated['trial'] ?? false);

        // A trial member holds no credits and their episodes arrive locked; a pack
        // grants credits and ends a trial. Asking for both asks for opposite things.
        if ($trial && isset($validated['pack'])) {
            throw ValidationException::withMessages([
                'pack' => 'A trial member cannot be granted a pack. Provision the trial without a pack, or grant the pack without the trial flag.',
            ]);
        }

        $pack = isset($validated['pack'])
            ? CreditPack::where('slug', $validated['pack'])->firstOrFail()
            : null;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)),
            'is_verified_partner' => false,
            'is_trial' => $trial,
            'trial_allowance' => $trial ? User::DEFAULT_TRIAL_ALLOWANCE : 0,
            'credits' => 0,
        ]);

        // Not mass-assignable, and the account is vetted off-platform before it
        // is created, so there is nothing for the member to confirm.
        $user->markEmailAsVerified();

        $user->assignRole('user');

        if ($pack) {
            $pack->grantTo($user);
        }

        $token = Password::createToken($user);
        $user->notify(new AccountCreatedNotification($token));

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_verified_partner' => $user->is_verified_partner,
                'is_trial' => $user->is_trial,
                'trial_allowance' => $user->trial_allowance,
                'credits' => $user->credits,
            ],
            'pack' => $pack?->slug,
        ], 201);
    }

    /**
     * Mark an existing account as a verified business partner. Partner status
     * governs pack pricing and nothing else — this grants no credits, unlocks no
     * episodes, and leaves a trial running. Safe to repeat.
     */
    public function verifyPartner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update(['is_verified_partner' => true]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_verified_partner' => $user->is_verified_partner,
                'is_trial' => $user->is_trial,
                'credits' => $user->credits,
            ],
        ]);
    }

    public function deactivateAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update(['is_active' => false]);

        return response()->json([
            'email' => $user->email,
            'is_active' => $user->is_active,
        ]);
    }
}
