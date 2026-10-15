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
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class ShopController extends Controller
{
    public function index(Request $request): \Inertia\Response|RedirectResponse
    {
        $user = $request->user();

        // A Temporary VBP's way to more stories is converting to a full partner,
        // not buying a pack that would leave them capped at one 6-episode story.
        if ($user->is_temporary_vbp) {
            return to_route('stories.index');
        }
        $audience = CreditPack::audienceType($user); // 'partner' | 'storybot'

        $packs = CreditPack::active()->ofType($audience)->orderBy('price')
            ->get(['id', 'slug', 'label', 'type', 'credits', 'max_episodes', 'price', 'stripe_price_id']);

        $addon = CreditPack::active()->ofType('addon')->orderBy('price')
            ->first(['id', 'slug', 'label', 'type', 'credits', 'price', 'stripe_price_id']);

        return Inertia::render('Shop/Index', [
            'packs' => $packs,
            'addon' => $addon,
            'canBuyAddon' => $this->canBuyAddon($user),
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
                'pack_label' => $purchase->creditPack?->label ?? 'Bonus Pack',
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

        abort_if($user->is_temporary_vbp, 403, 'Temporary VBPs become a Verified Business Partner to get more credits.');

        // Add-on can only be bought once the user holds a main pack
        // (verified partners qualify automatically).
        abort_if(
            $pack->type === 'addon' && ! $this->canBuyAddon($user),
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

    // -------------------------------------------------------------------------
    // Non-VBP signup page — guests buy Silver or Gold, the account follows payment
    // -------------------------------------------------------------------------

    public function signup(Request $request): \Inertia\Response
    {
        return Inertia::render('Signup', [
            'purchasedEmail' => session('purchased_email'),
        ]);
    }

    public function signupCheckout(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate(['plan' => 'required|in:silver,gold']);

        $pack = CreditPack::active()
            ->where('slug', config("services.signup_packs.{$data['plan']}"))
            ->first();

        if (! $pack) {
            Log::warning('Signup checkout plan has no active pack', ['plan' => $data['plan']]);

            return back()->withErrors(['checkout' => 'This plan is not available right now. Please contact us to sign up.']);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $session = Session::create([
                'mode' => 'payment',
                'line_items' => [$this->lineItemFor($pack, ucfirst($data['plan']))],
                'customer_creation' => 'always',
                'phone_number_collection' => ['enabled' => true],
                'success_url' => route('signup.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('signup').'#pricing',
                'metadata' => ['pack_id' => (string) $pack->id, 'signup' => '1'],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe signup checkout failed', ['plan' => $data['plan'], 'error' => $e->getMessage()]);

            return back()->withErrors(['checkout' => "We couldn't open checkout right now. Please try again in a moment."]);
        }

        return Inertia::location($session->url);
    }

    public function signupSuccess(Request $request): RedirectResponse
    {
        $email = null;

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = Session::retrieve((string) $request->query('session_id'));

            if ($session->payment_status === 'paid') {
                $this->handleCheckoutCompleted($session);
                $email = $session->customer_details?->email;
            }
        } catch (\Exception $e) {
            Log::error('Stripe signup success error', ['error' => $e->getMessage()]);
        }

        return to_route('signup')->with('purchased_email', $email);
    }

    /** A pack without a Stripe price yet is charged at its listed price. */
    private function lineItemFor(CreditPack $pack, string $planName): array
    {
        if ($pack->stripe_price_id) {
            return ['price' => $pack->stripe_price_id, 'quantity' => 1];
        }

        return [
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => $pack->price,
                'product_data' => ['name' => "StoryCreator.Bot {$planName}"],
            ],
            'quantity' => 1,
        ];
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

    /**
     * The add-on tops up a main pack, so it needs one. Trial members are excluded
     * outright: a vetted trial member satisfies the partner condition on its own,
     * and an add-on is not a main pack, so buying one would leave them holding
     * credits with a library still locked.
     */
    private function canBuyAddon(User $user): bool
    {
        if ($user->is_trial) {
            return false;
        }

        return $user->is_verified_partner || $user->hasBoughtMainPack();
    }

    private function handleCheckoutCompleted(object $session): void
    {
        if (UserCredit::where('stripe_checkout_session_id', $session->id)->exists()) {
            return;
        }

        $user = $session->client_reference_id
            ? User::find($session->client_reference_id)
            : $this->signupBuyer($session);
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

    /** The account a signup-page checkout pays for, created on the spot for a new email. */
    private function signupBuyer(object $session): ?User
    {
        $email = $session->customer_details?->email ?? null;

        if (($session->metadata->signup ?? null) !== '1' || ! $email) {
            return null;
        }

        return User::provisionForPurchase($email, $session->customer_details?->name ?? null);
    }
}
