<?php

namespace App\Http\Controllers;

use App\Models\CreditPack;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class ShopController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $audience = CreditPack::audienceType($user); // 'partner' | 'storybot'

        $packs = CreditPack::active()->ofType($audience)->orderBy('price')
            ->get(['id', 'slug', 'label', 'type', 'credits', 'max_episodes', 'price', 'stripe_price_id']);

        $addon = CreditPack::active()->ofType('addon')->orderBy('price')
            ->first(['id', 'slug', 'label', 'type', 'credits', 'price', 'stripe_price_id']);

        return Inertia::render('Shop/Index', [
            'packs' => $packs,
            'addon' => $addon,
            'canBuyAddon' => $user->is_verified_partner || $user->hasBoughtMainPack(),
            'credits' => $user->isAdmin() ? null : $user->credits,
            'notice' => session('notice'),
        ]);
    }

    public function history(Request $request): \Inertia\Response
    {
        $purchases = $request->user()->purchases()
            ->with('creditPack:id,label,type')
            ->get()
            ->map(fn (UserCredit $purchase) => [
                'id' => $purchase->id,
                'pack_label' => $purchase->creditPack?->label ?? 'Unknown pack',
                'pack_type' => $purchase->creditPack?->type,
                'credits_granted' => $purchase->credits_granted,
                'amount_paid' => $purchase->amount_paid,
                'source' => $purchase->source,
                'purchased_at' => $purchase->purchased_at?->format('M j, Y'),
            ]);

        return Inertia::render('Billing/History', [
            'purchases' => $purchases,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pack_id' => 'required|integer|exists:credit_packs,id',
        ]);

        $pack = CreditPack::where('id', $data['pack_id'])->active()->firstOrFail();
        $user = $request->user();

        // Add-on can only be bought once the user holds a main pack
        // (verified partners qualify automatically).
        abort_if(
            $pack->type === 'addon' && ! $user->is_verified_partner && ! $user->hasBoughtMainPack(),
            422,
            'Buy a story pack first — the Credit Boost is a top-up add-on.'
        );

        // Partner-priced packs are only for verified partners (and vice-versa).
        abort_if(
            $pack->type !== 'addon' && $pack->type !== CreditPack::audienceType($user),
            403,
            'This pack is not available for your account.'
        );

        abort_if(
            is_null($pack->stripe_price_id),
            422,
            'This pack is not yet configured for online payment. Please contact support.'
        );

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price' => $pack->stripe_price_id,
                'quantity' => 1,
            ]],
            'success_url' => route('shop.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('shop.index'),
            'client_reference_id' => (string) $user->id,
            'metadata' => ['pack_id' => (string) $pack->id],
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function success(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if ($sessionId && ! UserCredit::where('stripe_checkout_session_id', $sessionId)->exists()) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $session = Session::retrieve($sessionId);
                if ($session->payment_status === 'paid') {
                    $this->handleCheckoutCompleted($session);
                }
            } catch (\Exception $e) {
                Log::error('Stripe shop success error', ['error' => $e->getMessage()]);
            }
        }

        return redirect()->route('stories.index')
            ->with('notice', 'Your credits have been added! You\'re ready to create.');
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

        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($event->data->object);
        }

        return response('OK', 200);
    }

    // -------------------------------------------------------------------------

    private function handleCheckoutCompleted(object $session): void
    {
        if (UserCredit::where('stripe_checkout_session_id', $session->id)->exists()) {
            return;
        }

        $user = User::find($session->client_reference_id);
        if (! $user) {
            return;
        }

        $pack = CreditPack::find((int) $session->metadata->pack_id);
        if (! $pack) {
            return;
        }

        $amount = isset($session->amount_total) ? (int) $session->amount_total : $pack->price;

        $pack->grantTo($user, $session->id, $amount);
    }
}
