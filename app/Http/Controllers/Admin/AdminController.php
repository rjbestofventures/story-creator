<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    // -------------------------------------------------------------------------
    // Module pages
    // -------------------------------------------------------------------------

    public function usersIndex(): Response
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('price_monthly')
            ->get(['id', 'slug', 'label', 'episode_limit', 'stories_per_month', 'refine_monthly', 'price_monthly', 'price_yearly', 'trial_months', 'is_active']);

        $users = User::with(['roles', 'activeSubscription.plan'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tier' => $user->roles->first()?->name ?? 'user',
                'subscription' => $user->activeSubscription ? [
                    'id' => $user->activeSubscription->id,
                    'plan_id' => $user->activeSubscription->plan_id,
                    'plan_slug' => $user->activeSubscription->plan->slug,
                    'plan_label' => $user->activeSubscription->plan->label,
                    'status' => $user->activeSubscription->status,
                    'billing_interval' => $user->activeSubscription->billing_interval,
                    'expires_at' => $user->activeSubscription->expires_at?->toDateString(),
                    'story_credits' => $user->activeSubscription->story_credits,
                    'refine_credits' => $user->activeSubscription->refine_credits,
                    'effective_episode_limit' => $user->activeSubscription->effectiveEpisodeLimit(),
                    'stories_per_month' => $user->activeSubscription->plan->stories_per_month,
                    'refine_monthly' => $user->activeSubscription->plan->refine_monthly,
                ] : null,
                'created_at' => $user->created_at->format('n/j/Y'),
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'plans' => $plans,
            'stats' => [
                'users' => User::count(),
                'stories' => 0,
            ],
        ]);
    }

    public function plansIndex(): Response
    {
        $plans = Plan::orderBy('price_monthly')
            ->get(['id', 'slug', 'label', 'episode_limit', 'stories_per_month', 'refine_monthly', 'price_monthly', 'price_yearly', 'trial_months', 'is_active']);

        return Inertia::render('Admin/Plans', ['plans' => $plans]);
    }

    public function storiesIndex(): Response
    {
        return Inertia::render('Admin/Stories');
    }

    public function billingIndex(): Response
    {
        return Inertia::render('Admin/Billing');
    }

    // -------------------------------------------------------------------------
    // User management
    // -------------------------------------------------------------------------

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'tier' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['tier']);

        return back();
    }

    public function updateProfile(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return back();
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return back();
    }

    public function assignPlan(Request $request, User $user)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_interval' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $interval = $validated['billing_interval'];

        $user->subscriptions()->whereIn('status', ['active', 'trialing'])->update(['status' => 'cancelled']);

        $expiresAt = match (true) {
            $plan->trial_months > 0 => now()->addMonths($plan->trial_months),
            $plan->isFree() => null,
            $interval === 'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_interval' => $interval,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'billing_period_ends_at' => now()->addMonth(),
            'story_credits' => $plan->stories_per_month,
            'refine_credits' => $plan->refine_monthly,
        ]);

        return back();
    }

    public function updateSubscription(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,trialing,cancelled,expired',
        ]);

        $user->subscriptions()->latest()->first()?->update(['status' => $validated['status']]);

        return back();
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'tier' => 'required|in:admin,user',
        ]);

        $user->syncRoles([$validated['tier']]);

        return back();
    }

    public function destroy(User $user)
    {
        $user->delete();

        return back();
    }

    // -------------------------------------------------------------------------
    // Plan management
    // -------------------------------------------------------------------------

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'episode_limit' => 'required|integer|min:1',
            'stories_per_month' => 'required|integer|min:0',
            'refine_monthly' => 'required|integer|min:0',
            'price_monthly' => 'required|integer|min:0',
            'price_yearly' => 'required|integer|min:0',
            'trial_months' => 'required|integer|min:0',
        ]);

        $slug = Str::slug($validated['label']);
        $suffix = 2;
        $base = $slug;
        while (Plan::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        Plan::create(array_merge($validated, ['slug' => $slug, 'is_active' => true]));

        return back();
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'label'                => 'required|string|max:100',
            'episode_limit'        => 'required|integer|min:1',
            'stories_per_month'    => 'required|integer|min:0',
            'refine_monthly'       => 'required|integer|min:0',
            'price_monthly'        => 'required|integer|min:0',
            'price_yearly'         => 'required|integer|min:0',
            'trial_months'         => 'required|integer|min:0',
            'is_active'            => 'required|boolean',
            'stripe_price_monthly' => 'nullable|string|max:255',
            'stripe_price_yearly'  => 'nullable|string|max:255',
        ]);

        $plan->update($validated);

        return back();
    }

    public function destroyPlan(Plan $plan)
    {
        $active = $plan->subscriptions()->whereIn('status', ['active', 'trialing'])->count();

        if ($active > 0) {
            return back()->withErrors(['plan' => "Cannot delete \"{$plan->label}\" — it has {$active} active subscriber(s)."]);
        }

        $plan->delete();

        return to_route('admin.plans.index');
    }
}
