<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SiteSetting;
use App\Models\Story;
use App\Models\User;
use App\Models\UserSubscription;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
            ->withCount('stories')
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
                'stories_total' => $user->stories_count,
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

    public function storiesIndex(Request $request): Response
    {
        $query = Story::with(['user', 'episodes'])
            ->withCount('episodes')
            ->latest();

        $userId = $request->integer('user_id');
        $filterUser = null;

        if ($userId) {
            $query->where('user_id', $userId);
            $filterUser = User::find($userId, ['id', 'name', 'email']);
        }

        $paginator = $query->paginate(25)->through(fn (Story $story) => [
            'id'             => $story->id,
            'title'          => $story->title,
            'status'         => $story->status,
            'episodes_count' => $story->episodes_count,
            'refines_used'   => $story->refines_used,
            'created_at'     => $story->created_at->format('n/j/Y'),
            'user'           => [
                'id'    => $story->user->id,
                'name'  => $story->user->name,
                'email' => $story->user->email,
            ],
        ]);

        return Inertia::render('Admin/Stories', [
            'stories'    => $paginator,
            'filterUser' => $filterUser,
        ]);
    }

    public function storyShow(Request $request, Story $story): Response
    {
        $story->load(['user', 'episodes']);

        return Inertia::render('Admin/StoryShow', [
            'story' => [
                'id'         => $story->id,
                'title'      => $story->title,
                'status'     => $story->status,
                'back'       => $request->query('back', 'user'),
                'created_at' => $story->created_at->format('n/j/Y'),
                'user'       => [
                    'id'    => $story->user->id,
                    'name'  => $story->user->name,
                    'email' => $story->user->email,
                ],
                'episodes'   => $story->episodes->map(fn ($ep) => [
                    'id'             => $ep->id,
                    'episode_number' => $ep->episode_number,
                    'title'          => $ep->title,
                    'content'        => $ep->content,
                    'format'         => $ep->format,
                    'status'         => $ep->status,
                    'created_at'     => $ep->created_at->format('n/j/Y'),
                ]),
            ],
        ]);
    }

    public function billingIndex(): Response
    {
        return Inertia::render('Admin/Billing');
    }

    public function settingsIndex(): \Illuminate\Http\RedirectResponse
    {
        return to_route('admin.settings.access');
    }

    public function accessSettingsIndex(): Response
    {
        return Inertia::render('Admin/Settings/Access', [
            'landing_lock_enabled'  => (bool) SiteSetting::get('landing_lock_enabled', false),
            'landing_lock_password' => SiteSetting::get('landing_lock_password', ''),
        ]);
    }

    public function updateAccessSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'landing_lock_enabled'  => 'required|boolean',
            'landing_lock_password' => 'nullable|string|max:100',
        ]);

        SiteSetting::set('landing_lock_enabled', $data['landing_lock_enabled'] ? '1' : '0');
        SiteSetting::set('landing_lock_password', $data['landing_lock_password'] ?? '');

        return back();
    }

    public function aiSettingsIndex(): Response
    {
        return Inertia::render('Admin/Settings/AI', [
            'anthropic_api_key' => SiteSetting::get('anthropic_api_key', ''),
            'env_key_set'       => (bool) env('ANTHROPIC_API_KEY'),
            'interview_model'   => SiteSetting::get('interview_model', 'claude-haiku-4-5-20251001'),
            'generation_model'  => SiteSetting::get('generation_model', 'claude-sonnet-4-6'),
        ]);
    }

    public function updateAiSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'anthropic_api_key' => 'nullable|string|max:255',
            'interview_model'   => 'required|string|max:100',
            'generation_model'  => 'required|string|max:100',
        ]);

        SiteSetting::set('anthropic_api_key', $data['anthropic_api_key'] ?? '');
        SiteSetting::set('interview_model',   $data['interview_model']);
        SiteSetting::set('generation_model',  $data['generation_model']);

        return back();
    }

    public function fetchModels(): \Illuminate\Http\JsonResponse
    {
        try {
            $client = new \Anthropic\Client(apiKey: config('anthropic.api_key'));
            $page   = $client->models->list(limit: 100);

            $models = collect($page->data)
                ->map(fn ($m) => [
                    'id'           => $m->id,
                    'display_name' => $m->displayName,
                    'max_tokens'   => $m->maxTokens,
                ])
                ->values()
                ->toArray();

            return response()->json(['models' => $models]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function stripeSettingsIndex(): Response
    {
        return Inertia::render('Admin/Settings/Stripe', [
            'stripe_key'            => SiteSetting::get('stripe_key', ''),
            'stripe_secret'         => SiteSetting::get('stripe_secret', ''),
            'stripe_webhook_secret' => SiteSetting::get('stripe_webhook_secret', ''),
            'env_key_set'           => (bool) env('STRIPE_KEY'),
            'env_secret_set'        => (bool) env('STRIPE_SECRET'),
            'env_webhook_set'       => (bool) env('STRIPE_WEBHOOK_SECRET'),
        ]);
    }

    public function updateStripeSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'stripe_key'            => 'nullable|string|max:255',
            'stripe_secret'         => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
        ]);

        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value ?? '');
        }

        return back();
    }

    // -------------------------------------------------------------------------
    // User management
    // -------------------------------------------------------------------------

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'tier' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['tier']);

        $token = Password::createToken($user);
        $user->notify(new AccountCreatedNotification($token));

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

    public function userInvoices(User $user)
    {
        if (! $user->stripe_id) {
            return response()->json(['invoices' => [], 'has_stripe' => false]);
        }

        try {
            $invoices = $user->invoices()->map(fn ($inv) => [
                'id'     => $inv->id,
                'number' => $inv->number ?? $inv->id,
                'date'   => $inv->date()->format('M j, Y'),
                'total'  => $inv->total(),
                'status' => $inv->status,
            ])->values();
        } catch (\Exception) {
            return response()->json(['invoices' => [], 'has_stripe' => true]);
        }

        return response()->json(['invoices' => $invoices, 'has_stripe' => true]);
    }

    public function impersonate(User $user)
    {
        abort_if($user->id === Auth::user()->id, 403, 'Cannot impersonate yourself.');
        abort_if(session()->has('impersonating_admin_id'), 403, 'Already impersonating a user.');

        session(['impersonating_admin_id' => auth()->id()]);
        auth()->login($user);

        return redirect()->route('stories.index');
    }

    public function stopImpersonating()
    {
        $adminId = session('impersonating_admin_id');
        abort_unless($adminId, 403);

        session()->forget('impersonating_admin_id');
        auth()->loginUsingId($adminId);

        return redirect()->route('admin.users.index');
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
