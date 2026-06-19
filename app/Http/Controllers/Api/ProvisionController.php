<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProvisionController extends Controller
{
    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', Rule::unique('users', 'email')],
            'plan'             => ['required', 'string', Rule::exists('plans', 'slug')],
            'billing_interval' => ['sometimes', 'string', Rule::in(['monthly', 'yearly'])],
        ]);

        $plan     = Plan::where('slug', $validated['plan'])->firstOrFail();
        $interval = $validated['billing_interval'] ?? 'monthly';

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('user');

        $token = Password::createToken($user);
        $user->notify(new AccountCreatedNotification($token));

        $expiresAt = match (true) {
            $plan->trial_months > 0  => now()->addMonths($plan->trial_months),
            $plan->isFree()          => null,
            $interval === 'yearly'   => now()->addYear(),
            default                  => now()->addMonth(),
        };

        $subscription = UserSubscription::create([
            'user_id'               => $user->id,
            'plan_id'               => $plan->id,
            'billing_interval'      => $interval,
            'status'                => 'active',
            'starts_at'             => now(),
            'expires_at'            => $expiresAt,
            'billing_period_ends_at' => now()->addMonth(),
            'story_credits'         => $plan->stories_per_month,
            'refine_credits'        => $plan->refine_monthly,
        ]);

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'subscription' => [
                'plan'       => $plan->slug,
                'status'     => $subscription->status,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ],
        ], 201);
    }
}
