<?php

namespace App\Http\Controllers;

use App\Models\CreditPack;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Non-VBP way in: pick Silver or Gold on the signup page, register and
 * verify, then confirm the plan here and pay.
 */
class RetailPlanController extends Controller
{
    private const PARTNER_NOTICE = "You're a Verified Business Partner, so your plan is managed by our team.";

    public function start(Request $request, string $plan): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return to_route('register', ['plan' => $plan]);
        }

        if ($user->isFullPartner()) {
            return to_route('stories.index')->with('notice', self::PARTNER_NOTICE);
        }

        $user->update(['signup_plan' => $plan]);

        return to_route('plans.choose');
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isFullPartner()) {
            return to_route('stories.index')->with('notice', self::PARTNER_NOTICE);
        }

        return Inertia::render('ChoosePlan', [
            'plans' => collect(User::RETAIL_PLANS)
                ->map(fn (string $plan) => ['key' => $plan, 'pack_id' => CreditPack::forRetailPlan($plan)?->id])
                ->all(),
            'selected' => $user->signup_plan,
            'justVerified' => $request->boolean('verified'),
        ]);
    }
}
