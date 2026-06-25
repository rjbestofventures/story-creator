<?php

namespace App\Http\Controllers\Admin;

use Anthropic\Client;
use App\Http\Controllers\Controller;
use App\Models\CreditPack;
use App\Models\SiteSetting;
use App\Models\Story;
use App\Models\User;
use App\Models\UserCredit;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        $creditPacks = CreditPack::active()->orderBy('price')
            ->get(['id', 'slug', 'label', 'price', 'episode_limit', 'revision_credits']);

        $users = User::with(['roles', 'availableCredits.creditPack'])
            ->withCount('stories')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tier' => $user->roles->first()?->name ?? 'user',
                'is_active' => $user->is_active,
                'refine_credits' => $user->refine_credits,
                'available_packs' => $user->availableCredits
                    ->groupBy('credit_pack_id')
                    ->map(fn ($credits) => [
                        'pack' => $credits->first()->creditPack?->only('id', 'slug', 'label', 'episode_limit', 'revision_credits'),
                        'count' => $credits->count(),
                    ])
                    ->values(),
                'stories_total' => $user->stories_count,
                'created_at' => $user->created_at->format('n/j/Y'),
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'creditPacks' => $creditPacks,
            'stats' => [
                'users' => User::count(),
                'stories' => 0,
            ],
        ]);
    }

    public function packsIndex(): Response
    {
        $packs = CreditPack::orderBy('price')
            ->get(['id', 'slug', 'label', 'price', 'episode_limit', 'revision_credits', 'stripe_price_id', 'is_active'])
            ->map(fn (CreditPack $pack) => [
                'id' => $pack->id,
                'slug' => $pack->slug,
                'label' => $pack->label,
                'price_dollars' => $pack->price / 100,
                'episode_limit' => $pack->episode_limit,
                'revision_credits' => $pack->revision_credits,
                'stripe_price_id' => $pack->stripe_price_id,
                'is_active' => $pack->is_active,
            ]);

        return Inertia::render('Admin/Packs', ['packs' => $packs]);
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
            'id' => $story->id,
            'title' => $story->title,
            'status' => $story->status,
            'episodes_count' => $story->episodes_count,
            'refines_used' => $story->refines_used,
            'created_at' => $story->created_at->format('n/j/Y'),
            'user' => [
                'id' => $story->user->id,
                'name' => $story->user->name,
                'email' => $story->user->email,
            ],
        ]);

        return Inertia::render('Admin/Stories', [
            'stories' => $paginator,
            'filterUser' => $filterUser,
        ]);
    }

    public function storyShow(Request $request, Story $story): Response
    {
        $story->load(['user', 'episodes']);

        return Inertia::render('Admin/StoryShow', [
            'story' => [
                'id' => $story->id,
                'title' => $story->title,
                'status' => $story->status,
                'back' => $request->query('back', 'user'),
                'created_at' => $story->created_at->format('n/j/Y'),
                'user' => [
                    'id' => $story->user->id,
                    'name' => $story->user->name,
                    'email' => $story->user->email,
                ],
                'episodes' => $story->episodes->map(fn ($ep) => [
                    'id' => $ep->id,
                    'episode_number' => $ep->episode_number,
                    'title' => $ep->title,
                    'content' => $ep->content,
                    'format' => $ep->format,
                    'status' => $ep->status,
                    'created_at' => $ep->created_at->format('n/j/Y'),
                ]),
            ],
        ]);
    }

    public function billingIndex(): Response
    {
        $genPrice = [
            'input' => (float) SiteSetting::get('generation_price_input', 3.00),
            'output' => (float) SiteSetting::get('generation_price_output', 15.00),
        ];
        $intPrice = [
            'input' => (float) SiteSetting::get('interview_price_input', 0.80),
            'output' => (float) SiteSetting::get('interview_price_output', 4.00),
        ];

        $storyCost = function (Story $story) use ($genPrice, $intPrice) {
            return
                ($story->tokens_input * $genPrice['input'] +
                 $story->tokens_output * $genPrice['output'] +
                 $story->tokens_interview_input * $intPrice['input'] +
                 $story->tokens_interview_output * $intPrice['output']) / 1_000_000;
        };

        $stories = Story::with('user')
            ->select(['id', 'user_id', 'generation_model', 'interview_model',
                'tokens_input', 'tokens_output',
                'tokens_interview_input', 'tokens_interview_output'])
            ->get();

        $totalCost = 0;
        $totalGenInput = 0;
        $totalGenOutput = 0;
        $totalIntInput = 0;
        $totalIntOutput = 0;
        $userBuckets = [];

        foreach ($stories as $story) {
            $cost = $storyCost($story);
            $totalCost += $cost;
            $totalGenInput += $story->tokens_input;
            $totalGenOutput += $story->tokens_output;
            $totalIntInput += $story->tokens_interview_input;
            $totalIntOutput += $story->tokens_interview_output;

            $uid = $story->user_id;
            if (! isset($userBuckets[$uid])) {
                $userBuckets[$uid] = [
                    'id' => $uid,
                    'name' => $story->user->name,
                    'email' => $story->user->email,
                    'stories_count' => 0,
                    'cost' => 0,
                    'gen_input' => 0,
                    'gen_output' => 0,
                    'int_input' => 0,
                    'int_output' => 0,
                ];
            }

            $userBuckets[$uid]['stories_count']++;
            $userBuckets[$uid]['cost'] += $cost;
            $userBuckets[$uid]['gen_input'] += $story->tokens_input;
            $userBuckets[$uid]['gen_output'] += $story->tokens_output;
            $userBuckets[$uid]['int_input'] += $story->tokens_interview_input;
            $userBuckets[$uid]['int_output'] += $story->tokens_interview_output;
        }

        $perUser = collect($userBuckets)
            ->filter(fn ($u) => $u['stories_count'] > 0)
            ->sortByDesc('cost')
            ->map(fn ($u) => array_merge($u, ['cost' => round($u['cost'], 4)]))
            ->values();

        return Inertia::render('Admin/Billing', [
            'totals' => [
                'gen_input' => $totalGenInput,
                'gen_output' => $totalGenOutput,
                'int_input' => $totalIntInput,
                'int_output' => $totalIntOutput,
                'total_stories' => $stories->count(),
                'total_cost' => round($totalCost, 4),
            ],
            'models' => [
                'interview' => SiteSetting::get('interview_model', 'claude-haiku-4-5-20251001'),
                'generation' => SiteSetting::get('generation_model', 'claude-sonnet-4-6'),
                'int_price_input' => $intPrice['input'],
                'int_price_output' => $intPrice['output'],
                'gen_price_input' => $genPrice['input'],
                'gen_price_output' => $genPrice['output'],
            ],
            'perUser' => $perUser,
        ]);
    }

    public function settingsIndex(): RedirectResponse
    {
        return to_route('admin.settings.access');
    }

    public function accessSettingsIndex(): Response
    {
        return Inertia::render('Admin/Settings/Access', [
            'landing_lock_enabled' => (bool) SiteSetting::get('landing_lock_enabled', false),
            'landing_lock_password' => SiteSetting::get('landing_lock_password', ''),
        ]);
    }

    public function updateAccessSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'landing_lock_enabled' => 'required|boolean',
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
            'env_key_set' => (bool) env('ANTHROPIC_API_KEY'),
            'interview_model' => SiteSetting::get('interview_model', 'claude-haiku-4-5-20251001'),
            'generation_model' => SiteSetting::get('generation_model', 'claude-sonnet-4-6'),
            'interview_price_input' => (float) SiteSetting::get('interview_price_input', 0.80),
            'interview_price_output' => (float) SiteSetting::get('interview_price_output', 4.00),
            'generation_price_input' => (float) SiteSetting::get('generation_price_input', 3.00),
            'generation_price_output' => (float) SiteSetting::get('generation_price_output', 15.00),
        ]);
    }

    public function updateAiSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'anthropic_api_key' => 'nullable|string|max:255',
            'interview_model' => 'required|string|max:100',
            'generation_model' => 'required|string|max:100',
            'interview_price_input' => 'required|numeric|min:0',
            'interview_price_output' => 'required|numeric|min:0',
            'generation_price_input' => 'required|numeric|min:0',
            'generation_price_output' => 'required|numeric|min:0',
        ]);

        SiteSetting::set('anthropic_api_key', $data['anthropic_api_key'] ?? '');
        SiteSetting::set('interview_model', $data['interview_model']);
        SiteSetting::set('generation_model', $data['generation_model']);
        SiteSetting::set('interview_price_input', $data['interview_price_input']);
        SiteSetting::set('interview_price_output', $data['interview_price_output']);
        SiteSetting::set('generation_price_input', $data['generation_price_input']);
        SiteSetting::set('generation_price_output', $data['generation_price_output']);

        return back();
    }

    public function fetchModels(): JsonResponse
    {
        try {
            $client = new Client(apiKey: config('anthropic.api_key'));
            $page = $client->models->list(limit: 100);

            $models = collect($page->data)
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'display_name' => $m->displayName,
                    'max_tokens' => $m->maxTokens,
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
            'stripe_key' => SiteSetting::get('stripe_key', ''),
            'stripe_secret' => SiteSetting::get('stripe_secret', ''),
            'stripe_webhook_secret' => SiteSetting::get('stripe_webhook_secret', ''),
            'env_key_set' => (bool) env('STRIPE_KEY'),
            'env_secret_set' => (bool) env('STRIPE_SECRET'),
            'env_webhook_set' => (bool) env('STRIPE_WEBHOOK_SECRET'),
        ]);
    }

    public function updateStripeSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stripe_key' => 'nullable|string|max:255',
            'stripe_secret' => 'nullable|string|max:255',
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
            'tier' => 'required|in:super_admin,admin,user',
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

    public function toggleStatus(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        return back();
    }

    public function resetPassword(Request $request, User $user)
    {
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('password_reset_sent', $user->email);
    }

    public function assignPlan(Request $request, User $user)
    {
        $validated = $request->validate([
            'pack_id' => 'required|exists:credit_packs,id',
        ]);

        $pack = CreditPack::findOrFail($validated['pack_id']);

        UserCredit::create([
            'user_id' => $user->id,
            'credit_pack_id' => $pack->id,
            'episode_limit' => $pack->episode_limit,
            'revision_credits_granted' => $pack->revision_credits,
            'status' => 'available',
            'purchased_at' => now(),
        ]);

        $user->increment('refine_credits', $pack->revision_credits);

        return back();
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'tier' => 'required|in:super_admin,admin,user',
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
                'id' => $inv->id,
                'number' => $inv->number ?? $inv->id,
                'date' => $inv->date()->format('M j, Y'),
                'total' => $inv->total(),
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
    // Credit pack management
    // -------------------------------------------------------------------------

    public function storePack(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'episode_limit' => 'required|integer|min:1',
            'revision_credits' => 'required|integer|min:0',
            'price_dollars' => 'required|numeric|min:0',
            'stripe_price_id' => 'nullable|string|max:255',
        ]);

        $slug = Str::slug($validated['label']);
        $base = $slug;
        $suffix = 2;
        while (CreditPack::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        CreditPack::create([
            'slug' => $slug,
            'label' => $validated['label'],
            'price' => (int) round($validated['price_dollars'] * 100),
            'episode_limit' => $validated['episode_limit'],
            'revision_credits' => $validated['revision_credits'],
            'stripe_price_id' => $validated['stripe_price_id'] ?: null,
            'is_active' => true,
        ]);

        return back();
    }

    public function updatePack(Request $request, CreditPack $pack)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'episode_limit' => 'required|integer|min:1',
            'revision_credits' => 'required|integer|min:0',
            'price_dollars' => 'required|numeric|min:0',
            'stripe_price_id' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $pack->update([
            'label' => $validated['label'],
            'price' => (int) round($validated['price_dollars'] * 100),
            'episode_limit' => $validated['episode_limit'],
            'revision_credits' => $validated['revision_credits'],
            'stripe_price_id' => $validated['stripe_price_id'] ?: null,
            'is_active' => $validated['is_active'],
        ]);

        return back();
    }

    public function destroyPack(CreditPack $pack)
    {
        if ($pack->userCredits()->exists()) {
            return back()->withErrors(['pack' => "Cannot delete \"{$pack->label}\" — it has purchase history. Deactivate it instead."]);
        }

        $pack->delete();

        return to_route('admin.packs.index');
    }
}
