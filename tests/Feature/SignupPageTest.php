<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\CreditPack;
use App\Models\Story;
use App\Models\User;
use App\Notifications\NonVbpSignupNotification;
use App\Notifications\RetailPackPurchasedNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The Non-VBP way in: Silver/Gold on /signup → register → verify → choose
 * plan → pay → create the first story.
 */
class SignupPageTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test';

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('user');
        config(['services.stripe.webhook.secret' => self::WEBHOOK_SECRET]);
    }

    private function gold(): CreditPack
    {
        return CreditPack::create([
            'slug' => 'storybot-gold-test', 'label' => 'Gold', 'type' => 'storybot',
            'credits' => 36, 'max_episodes' => 24, 'price' => 27000, 'is_active' => true,
        ]);
    }

    private function registration(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Jamie',
            'last_name' => 'Rivera',
            'phone' => '3478245640',
            'email' => 'jamie@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan' => 'gold',
        ];
    }

    /** Deliver a checkout.session.completed event the way Stripe signs it. */
    private function completeCheckout(User $user, CreditPack $pack, string $sessionId = 'cs_test_1'): void
    {
        $payload = json_encode([
            'id' => 'evt_test',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'client_reference_id' => (string) $user->id,
                'amount_total' => $pack->price,
                'payment_status' => 'paid',
                'metadata' => ['pack_id' => (string) $pack->id],
            ]],
        ]);
        $time = time();
        $signature = hash_hmac('sha256', "{$time}.{$payload}", self::WEBHOOK_SECRET);

        $this->call('POST', route('stripe.webhook'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$time},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();
    }

    // -------------------------------------------------------------------------
    // Signup page → register
    // -------------------------------------------------------------------------

    public function test_signup_page_renders(): void
    {
        $this->get(route('signup'))->assertOk()->assertInertia(fn ($page) => $page->component('Signup'));
    }

    public function test_guest_picking_a_plan_goes_to_register_with_it(): void
    {
        $this->get(route('signup.plan', 'gold'))->assertRedirect(route('register', ['plan' => 'gold']));

        $this->get(route('register', ['plan' => 'gold']))
            ->assertInertia(fn ($page) => $page->component('Auth/Register')->where('plan', 'gold'));
    }

    public function test_unknown_plans_are_not_routes(): void
    {
        $this->get('/signup/platinum')->assertNotFound();
    }

    public function test_registering_saves_phone_and_plan_and_asks_for_verification(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->registration())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'jamie@example.com')->first();
        $this->assertSame('Jamie Rivera', $user->name);
        $this->assertSame('3478245640', $user->phone);
        $this->assertSame('gold', $user->signup_plan);
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
        Notification::assertSentOnDemand(NonVbpSignupNotification::class);
    }

    public function test_registering_requires_a_us_phone(): void
    {
        $this->post(route('register'), $this->registration(['phone' => '12345']))->assertSessionHasErrors('phone');
    }

    public function test_registering_with_a_known_email_points_to_login(): void
    {
        User::factory()->create(['email' => 'jamie@example.com']);

        $this->post(route('register'), $this->registration())
            ->assertSessionHasErrors(['email' => 'You already have an account. Log in to continue.']);
    }

    public function test_logging_in_from_the_signup_flow_returns_to_the_picked_plan(): void
    {
        $user = User::factory()->create(['email' => 'jamie@example.com']);

        $this->get(route('login', ['plan' => 'silver']));

        $this->post(route('login'), ['email' => 'jamie@example.com', 'password' => 'password'])
            ->assertRedirect(route('signup.plan', 'silver'));

        $this->assertAuthenticatedAs($user);
    }

    // -------------------------------------------------------------------------
    // Verify → choose plan
    // -------------------------------------------------------------------------

    public function test_verifying_a_signup_lands_on_the_plan_page(): void
    {
        $user = User::factory()->unverified()->create(['signup_plan' => 'gold']);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->get($url)->assertRedirect(route('plans.choose', ['verified' => 1]));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verifying_without_a_signup_plan_goes_to_the_library(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->get($url)->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_plan_page_highlights_the_pick_and_confirms_verification(): void
    {
        config(['services.signup_packs.gold' => 'storybot-gold-test']);
        $pack = $this->gold();
        $user = User::factory()->create(['signup_plan' => 'gold']);

        $this->actingAs($user)->get(route('plans.choose', ['verified' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ChoosePlan')
                ->where('selected', 'gold')
                ->where('justVerified', true)
                ->where('plans.1.key', 'gold')
                ->where('plans.1.pack_id', $pack->id));
    }

    public function test_plan_page_waits_for_email_verification(): void
    {
        $user = User::factory()->unverified()->create(['signup_plan' => 'gold']);

        $this->actingAs($user)->get(route('plans.choose'))->assertRedirect(route('verification.notice'));
    }

    public function test_logged_in_member_picking_a_plan_skips_signup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('signup.plan', 'silver'))->assertRedirect(route('plans.choose'));
        $this->assertSame('silver', $user->fresh()->signup_plan);
    }

    public function test_verified_partners_are_told_their_plan_is_managed(): void
    {
        $partner = User::factory()->create(['is_verified_partner' => true, 'vbp_plan' => 'gold']);

        $this->actingAs($partner)->get(route('signup.plan', 'gold'))
            ->assertRedirect(route('stories.index'))
            ->assertSessionHas('notice');

        $this->actingAs($partner)->get(route('plans.choose'))->assertRedirect(route('stories.index'));
    }

    // -------------------------------------------------------------------------
    // Pay → create
    // -------------------------------------------------------------------------

    public function test_complimentary_trial_can_start_a_retail_checkout_but_not_an_addon(): void
    {
        config(['services.stripe.secret' => null]);
        $pack = $this->gold();
        $addon = CreditPack::create(['slug' => 'boost', 'label' => 'Boost', 'type' => 'addon', 'credits' => 12, 'max_episodes' => 24, 'price' => 4500, 'is_active' => true]);
        $user = User::factory()->create();
        $user->becomeTemporaryPartner();

        // Past every eligibility check; only the missing Stripe key stops it.
        $this->actingAs($user)->postJson(route('shop.checkout'), ['pack_id' => $pack->id, 'from' => 'plan'])
            ->assertStatus(502)
            ->assertJsonPath('message', "We couldn't open checkout right now. Please try again in a moment.");

        $this->actingAs($user)->postJson(route('shop.checkout'), ['pack_id' => $addon->id])->assertForbidden();
    }

    public function test_buying_gold_ends_a_complimentary_trial_and_unlocks_its_story(): void
    {
        Notification::fake();
        $pack = $this->gold();
        $user = User::factory()->create(['credits' => 0]);
        $user->becomeTemporaryPartner();
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create(['created_on_trial' => true]);

        $this->completeCheckout($user, $pack);

        $user->refresh();
        $this->assertFalse($user->is_temporary_vbp);
        $this->assertNull($user->temporary_vbp_expires_at);
        $this->assertSame(User::TEMPORARY_VBP_CREDITS + 36, $user->credits);
        $this->assertFalse($story->fresh()->locksEpisodes());
        Notification::assertSentOnDemand(RetailPackPurchasedNotification::class);
    }

    public function test_first_purchase_lands_on_create_story(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('shop.success'))
            ->assertRedirect(route('stories.create'))
            ->assertSessionHas('notice', "Payment received. Your credits are ready, let's create your first story.");
    }

    public function test_returning_buyer_lands_on_the_library(): void
    {
        $user = User::factory()->create();
        Story::factory()->for($user)->for(BusinessProfile::factory()->for($user)->create())->create();

        $this->actingAs($user)->get(route('shop.success'))->assertRedirect(route('stories.index'));
    }

    // -------------------------------------------------------------------------
    // Trial form on the same page
    // -------------------------------------------------------------------------

    public function test_trial_form_on_signup_page_creates_a_complimentary_trial(): void
    {
        Http::fake();
        Notification::fake();

        $this->from(route('signup'))
            ->post(route('trial.signup.submit'), [
                'first_name' => 'Jamie',
                'last_name' => 'Rivera',
                'phone' => '3478245640',
                'email' => 'jamie@example.com',
            ])
            ->assertRedirect(route('signup'));

        $this->assertTrue(User::where('email', 'jamie@example.com')->first()->is_temporary_vbp);
    }
}
