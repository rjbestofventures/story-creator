<?php

namespace Tests\Feature;

use App\Models\CreditPack;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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

    private function silverPack(): CreditPack
    {
        return CreditPack::create([
            'slug' => 'storybot-basic', 'label' => 'Basic', 'type' => 'storybot',
            'credits' => 24, 'max_episodes' => 12, 'price' => 18000, 'is_active' => true,
        ]);
    }

    /** Deliver a checkout.session.completed event the way Stripe signs it. */
    private function completeCheckout(CreditPack $pack, string $email, string $sessionId = 'cs_test_1'): void
    {
        $payload = json_encode([
            'id' => 'evt_test',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'client_reference_id' => null,
                'amount_total' => $pack->price,
                'payment_status' => 'paid',
                'customer_details' => ['email' => $email, 'name' => 'Jamie Rivera'],
                'metadata' => ['pack_id' => (string) $pack->id, 'signup' => '1'],
            ]],
        ]);
        $time = time();
        $signature = hash_hmac('sha256', "{$time}.{$payload}", self::WEBHOOK_SECRET);

        $this->call('POST', route('stripe.webhook'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$time},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();
    }

    public function test_signup_page_renders(): void
    {
        $this->get(route('signup'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Signup')->where('purchasedEmail', null));
    }

    public function test_checkout_only_sells_silver_or_gold(): void
    {
        $this->post(route('signup.checkout'), ['plan' => 'platinum'])->assertSessionHasErrors('plan');
    }

    public function test_paid_signup_checkout_creates_the_account_and_grants_the_pack(): void
    {
        Notification::fake();
        $pack = $this->silverPack();

        $this->completeCheckout($pack, 'jamie@example.com');

        $user = User::where('email', 'jamie@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Jamie Rivera', $user->name);
        $this->assertSame(24, $user->credits);
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->is_verified_partner);
        Notification::assertSentTo($user, AccountCreatedNotification::class);
    }

    public function test_signup_checkout_for_a_known_email_tops_up_that_account(): void
    {
        Notification::fake();
        $pack = $this->silverPack();
        $user = User::factory()->create(['email' => 'jamie@example.com', 'credits' => 3]);

        $this->completeCheckout($pack, 'jamie@example.com');
        $this->completeCheckout($pack, 'jamie@example.com');

        $this->assertSame(27, $user->fresh()->credits);
        $this->assertSame(1, User::where('email', 'jamie@example.com')->count());
        Notification::assertNothingSent();
    }

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
