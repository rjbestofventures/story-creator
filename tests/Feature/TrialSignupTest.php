<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\TrialAccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrialSignupTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'first_name' => 'Tess',
        'last_name' => 'Trial',
        'email' => 'tess@example.com',
        'phone' => '5614502121',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('user');
        Notification::fake();
    }

    public function test_the_form_page_renders(): void
    {
        $this->get(route('trial.signup'))->assertOk();
    }

    public function test_signing_up_creates_a_complementary_trial_and_notifies_the_admin(): void
    {
        $this->post(route('trial.signup.submit'), $this->payload)->assertSessionHas('submitted');

        $user = User::where('email', 'tess@example.com')->firstOrFail();

        $this->assertSame('Tess Trial', $user->name);
        $this->assertTrue($user->is_temporary_vbp);
        $this->assertFalse($user->is_trial);
        $this->assertSame(User::TEMPORARY_VBP_CREDITS, $user->credits);
        $this->assertTrue($user->hasRole('user'));

        Notification::assertSentTo($user, AccountCreatedNotification::class);
        Notification::assertSentOnDemand(TrialAccountCreatedNotification::class);
    }

    public function test_the_lead_is_posted_to_the_crm_when_a_webhook_is_configured(): void
    {
        config(['services.crm.trial_webhook' => 'https://crm.test/hook']);
        Http::fake();

        $this->post(route('trial.signup.submit'), $this->payload);

        Http::assertSent(fn ($request) => $request->url() === 'https://crm.test/hook'
            && $request['email'] === 'tess@example.com'
            && $request['phone'] === '5614502121'
            && $request['source'] === 'storybot_trial_signup');
    }

    public function test_a_crm_outage_does_not_block_the_signup(): void
    {
        config(['services.crm.trial_webhook' => 'https://crm.test/hook']);
        Http::fake(['*' => Http::response('down', 500)]);

        $this->post(route('trial.signup.submit'), $this->payload)->assertSessionHas('submitted');

        $this->assertDatabaseHas('users', ['email' => 'tess@example.com']);
    }

    public function test_the_phone_must_be_ten_digits_and_the_email_unused(): void
    {
        User::factory()->create(['email' => 'tess@example.com']);

        $this->post(route('trial.signup.submit'), [...$this->payload, 'phone' => '15614502121'])
            ->assertSessionHasErrors(['phone', 'email']);
    }
}
