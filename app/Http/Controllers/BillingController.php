<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Webhook;

class BillingController extends Controller
{
    public function plans(Request $request): \Inertia\Response
    {
        $plans = Plan::where('is_active', true)
            ->where('slug', '!=', 'partner')
            ->orderBy('price_monthly')
            ->get(['id', 'slug', 'label', 'episode_limit', 'stories_per_month',
                'refine_monthly', 'price_monthly', 'price_yearly', 'trial_months']);

        return Inertia::render('Billing/Plans', [
            'plans' => $plans,
            'notice' => session('notice'),
        ]);
    }

    public function selectFree(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $plan = Plan::where('id', $data['plan_id'])
            ->where('is_active', true)
            ->where('price_monthly', 0)
            ->firstOrFail();

        $this->cancelExistingSubscriptions($request->user());

        UserSubscription::create([
            'user_id' => $request->user()->id,
            'plan_id' => $plan->id,
            'billing_interval' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => null,
            'billing_period_ends_at' => now()->addMonth(),
            'story_credits' => $plan->stories_per_month,
            'refine_credits' => $plan->refine_monthly,
        ]);

        return redirect()->route('stories.index');
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::where('id', $data['plan_id'])
            ->where('is_active', true)
            ->where('price_monthly', '>', 0)
            ->firstOrFail();

        $priceId = $plan->stripePriceId($data['interval']);

        abort_if(is_null($priceId), 422, 'This plan is not yet configured for online payment. Please contact support.');

        $session = $request->user()->checkout([$priceId => 1], [
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.plans'),
            'mode' => 'subscription',
            'client_reference_id' => (string) $request->user()->id,
            'metadata' => [
                'plan_id' => (string) $plan->id,
                'interval' => $data['interval'],
            ],
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function success(Request $request): \Inertia\Response|RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if ($sessionId && ! $request->user()->hasActiveSubscription()) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $session = Session::retrieve($sessionId);
                if ($session->payment_status === 'paid') {
                    $this->handleCheckoutCompleted($session);
                }
            } catch (\Exception $e) {
                Log::error('Stripe success page error', ['error' => $e->getMessage()]);
            }
        }

        if ($request->user()->fresh()->hasActiveSubscription()) {
            return redirect()->route('stories.index');
        }

        return Inertia::render('Billing/Success');
    }

    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook.secret');

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'invoice.paid' => $this->handleInvoicePaid($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            default => null,
        };

        return response('OK', 200);
    }

    // -------------------------------------------------------------------------

    private function handleCheckoutCompleted(object $session): void
    {
        if (UserSubscription::where('stripe_checkout_session_id', $session->id)->exists()) {
            return;
        }

        $user = User::find($session->client_reference_id);
        if (! $user) {
            return;
        }

        if (! $user->stripe_id) {
            $user->forceFill(['stripe_id' => $session->customer])->save();
        }

        $planId = (int) $session->metadata->plan_id;
        $interval = $session->metadata->interval;
        $plan = Plan::find($planId);

        if (! $plan) {
            return;
        }

        if (! $session->subscription) {
            return;
        }

        $stripeSub = Subscription::retrieve($session->subscription);
        $periodEnd = $stripeSub->current_period_end
            ? Carbon::createFromTimestamp($stripeSub->current_period_end)
            : now()->addMonth();

        $this->cancelExistingSubscriptions($user);

        UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_interval' => $interval,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => null,
            'billing_period_ends_at' => $periodEnd,
            'story_credits' => $plan->stories_per_month,
            'refine_credits' => $plan->refine_monthly,
            'stripe_subscription_id' => $session->subscription,
            'stripe_checkout_session_id' => $session->id,
        ]);
    }

    private function handleInvoicePaid(object $invoice): void
    {
        if ($invoice->billing_reason !== 'subscription_cycle') {
            return;
        }

        $sub = UserSubscription::where('stripe_subscription_id', $invoice->subscription)->first();
        if (! $sub) {
            return;
        }

        $sub->update([
            'story_credits' => $sub->plan->stories_per_month,
            'refine_credits' => $sub->refine_credits + $sub->plan->refine_monthly,
            'billing_period_ends_at' => Carbon::createFromTimestamp($invoice->period_end),
            'status' => 'active',
        ]);
    }

    private function handleSubscriptionDeleted(object $stripeSub): void
    {
        $sub = UserSubscription::where('stripe_subscription_id', $stripeSub->id)->first();
        if (! $sub) {
            return;
        }

        $sub->update([
            'status' => 'cancelled',
            'expires_at' => Carbon::createFromTimestamp($stripeSub->current_period_end),
        ]);
    }

    private function cancelExistingSubscriptions(User $user): void
    {
        $user->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->update(['status' => 'cancelled']);
    }
}
