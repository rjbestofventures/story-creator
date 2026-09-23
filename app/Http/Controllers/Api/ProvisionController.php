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
            'vbp_plan' => ['sometimes', 'string', Rule::in(array_keys(User::VBP_PLAN_CREDITS))],
        ]);

        $trial = (bool) ($validated['trial'] ?? false);
        $plan = $validated['vbp_plan'] ?? null;

        // A plan makes the account a partner with the plan's credits, which a
        // trial (no credits) or a pack (its own credits) would contradict.
        if ($plan && ($trial || isset($validated['pack']))) {
            throw ValidationException::withMessages([
                'vbp_plan' => 'A VBP plan cannot be combined with a trial or a pack. The plan grants its own credits.',
            ]);
        }

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

        if ($plan) {
            $user->convertToPartner($plan);
        }

        $token = Password::createToken($user);
        $user->notify(new AccountCreatedNotification($token));

        return response()->json([
            'user' => $this->summarize($user->fresh()),
            'pack' => $pack?->slug,
        ], 201);
    }

    /**
     * Create a Temporary VBP: 12 credits, one 6-episode story, and an account
     * that shuts after three months unless it is converted to a full partner
     * through convertToPartner.
     */
    public function createTemporaryVbp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)),
            'credits' => 0,
        ]);

        $user->markEmailAsVerified();
        $user->assignRole('user');
        $user->becomeTemporaryPartner();

        $user->notify(new AccountCreatedNotification(Password::createToken($user)));

        return response()->json([
            'user' => $this->summarize($user->fresh()),
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
            'vbp_plan' => ['sometimes', 'string', Rule::in(array_keys(User::VBP_PLAN_CREDITS))],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update([
            'is_verified_partner' => true,
            'vbp_plan' => $validated['vbp_plan'] ?? $user->vbp_plan,
        ]);

        return response()->json([
            'user' => $this->summarize($user),
        ]);
    }

    /** The account shape every status endpoint returns. */
    private function summarize(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'is_verified_partner' => $user->is_verified_partner,
            'vbp_plan' => $user->vbp_plan,
            'is_temporary_vbp' => $user->is_temporary_vbp,
            'temporary_vbp_expires_at' => $user->temporary_vbp_expires_at?->toIso8601String(),
            'is_trial' => $user->is_trial,
            'trial_allowance' => $user->trial_allowance,
            'credits' => $user->credits,
        ];
    }

    /**
     * Convert a vetted trial member or Temporary VBP into a verified business
     * partner on a VBP plan: partner pricing and the plan's starting wallet. A
     * trial library stays locked — they spend those credits to open it.
     *
     * Distinct from verifyPartner, which sets pricing alone and grants nothing.
     */
    public function convertToPartner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'vbp_plan' => ['required', 'string', Rule::in(array_keys(User::VBP_PLAN_CREDITS))],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        $user->convertToPartner($validated['vbp_plan']);

        return response()->json([
            'user' => $this->summarize($user->fresh()),
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
            'user' => $this->summarize($user),
        ]);
    }
}
