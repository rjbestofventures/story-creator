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
        $packs = CreditPack::active()->orderBy('price')->get([
            'id', 'slug', 'label', 'price', 'episode_limit', 'revision_credits', 'stripe_price_id',
        ]);

        return Inertia::render('Shop/Index', [
            'packs'  => $packs,
            'notice' => session('notice'),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pack_id' => 'required|integer|exists:credit_packs,id',
        ]);

        $pack = CreditPack::where('id', $data['pack_id'])->active()->firstOrFail();

        abort_if(
            is_null($pack->stripe_price_id),
            422,
            'This pack is not yet configured for online payment. Please contact support.'
        );

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'line_items'           => [[
                'price'    => $pack->stripe_price_id,
                'quantity' => 1,
            ]],
            'success_url'          => route('shop.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'           => route('shop.index'),
            'client_reference_id'  => (string) $request->user()->id,
            'metadata'             => ['pack_id' => (string) $pack->id],
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
            ->with('notice', 'Your story pack has been added! You\'re ready to create.');
    }

    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook.secret');

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

        UserCredit::create([
            'user_id'                    => $user->id,
            'credit_pack_id'             => $pack->id,
            'episode_limit'              => $pack->episode_limit,
            'revision_credits_granted'   => $pack->revision_credits,
            'stripe_checkout_session_id' => $session->id,
            'status'                     => 'available',
            'purchased_at'               => now(),
        ]);

        $user->increment('refine_credits', $pack->revision_credits);
    }
}
