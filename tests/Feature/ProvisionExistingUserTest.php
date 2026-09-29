<?php

namespace Tests\Feature;

use App\Models\CreditPack;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProvisionExistingUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('user');
        config(['app.provision_api_token' => 'test-token']);
        Notification::fake();
    }

    private function provision(array $payload): TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/provision/user', $payload);
    }

    private function pack(): CreditPack
    {
        return CreditPack::create([
            'slug' => 'basic-partner',
            'label' => 'Basic Pack',
            'type' => 'partner',
            'credits' => 48,
            'max_episodes' => 12,
            'price' => 2000,
            'is_active' => true,
        ]);
    }

    public function test_a_new_account_is_flagged_as_created(): void
    {
        $this->provision(['name' => 'Jane Smith', 'email' => 'jane@example.com'])
            ->assertCreated()
            ->assertJsonPath('created', true);
    }

    public function test_an_existing_email_is_updated_with_the_plan_instead_of_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Original Name', 'email' => 'jane@example.com', 'credits' => 5]);

        $this->provision(['name' => 'Other Name', 'email' => 'jane@example.com', 'vbp_plan' => 'silver'])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.vbp_plan', 'silver')
            ->assertJsonPath('user.is_verified_partner', true)
            ->assertJsonPath('user.credits', 5 + 24);

        $this->assertSame('Original Name', $user->fresh()->name);
        $this->assertSame(1, User::where('email', 'jane@example.com')->count());
    }

    public function test_repeating_the_plan_on_a_full_partner_grants_no_credits_twice(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'credits' => 0]);
        $user->convertToPartner('gold');

        $this->provision(['name' => 'Jane', 'email' => 'jane@example.com', 'vbp_plan' => 'silver'])
            ->assertOk()
            ->assertJsonPath('user.vbp_plan', 'silver')
            ->assertJsonPath('user.credits', 36);
    }

    public function test_an_existing_email_is_granted_the_pack(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'credits' => 2]);
        $this->pack();

        $this->provision(['name' => 'Jane', 'email' => 'jane@example.com', 'pack' => 'basic-partner'])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('pack', 'basic-partner')
            ->assertJsonPath('user.credits', 50);

        $this->assertTrue($user->fresh()->is_verified_partner);
    }

    public function test_an_existing_email_does_not_start_a_trial(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'credits' => 0]);

        $this->provision(['name' => 'Jane', 'email' => 'jane@example.com', 'trial' => true])
            ->assertOk()
            ->assertJsonPath('created', false);

        $user->refresh();
        $this->assertFalse($user->is_temporary_vbp);
        $this->assertSame(0, $user->credits);
    }

    public function test_an_existing_email_gets_no_account_created_email(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->provision(['name' => 'Jane', 'email' => 'jane@example.com', 'vbp_plan' => 'gold'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_a_new_account_still_gets_the_account_created_email(): void
    {
        $this->provision(['name' => 'Jane', 'email' => 'jane@example.com'])->assertCreated();

        Notification::assertSentTo(User::where('email', 'jane@example.com')->first(), AccountCreatedNotification::class);
    }

    public function test_conflicting_options_still_fail_for_an_existing_email(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->provision(['name' => 'Jane', 'email' => 'jane@example.com', 'vbp_plan' => 'gold', 'trial' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vbp_plan');
    }
}
