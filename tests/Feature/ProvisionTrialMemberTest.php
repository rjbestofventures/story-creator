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

class ProvisionTrialMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('user');
        config(['app.provision_api_token' => 'test-token']);
    }

    private function provision(array $payload): TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/provision/user', $payload);
    }

    private function pack(string $type, string $slug): CreditPack
    {
        return CreditPack::create([
            'slug' => $slug,
            'label' => ucfirst($type).' Pack',
            'type' => $type,
            'credits' => 48,
            'max_episodes' => 12,
            'price' => 2000,
            'is_active' => true,
        ]);
    }

    public function test_provisioning_with_the_trial_flag_creates_a_trial_member(): void
    {
        Notification::fake();

        $this->provision(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'trial' => true])
            ->assertCreated()
            ->assertJsonPath('user.is_trial', true)
            ->assertJsonPath('user.trial_allowance', User::DEFAULT_TRIAL_ALLOWANCE)
            ->assertJsonPath('user.credits', 0);

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertTrue($user->is_trial);
        $this->assertSame(User::DEFAULT_TRIAL_ALLOWANCE, $user->trial_allowance);
        $this->assertFalse($user->is_verified_partner);
        $this->assertNotNull($user->email_verified_at);

        Notification::assertSentTo($user, AccountCreatedNotification::class);
    }

    public function test_a_trial_member_is_offered_retail_pricing(): void
    {
        $this->provision(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'trial' => true]);

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertSame('storybot', CreditPack::audienceType($user));
    }

    public function test_provisioning_a_trial_with_a_pack_is_rejected(): void
    {
        $this->pack('partner', 'partner-basic');

        $this->provision([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'trial' => true,
            'pack' => 'partner-basic',
        ])->assertStatus(422)->assertJsonValidationErrors('pack');

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_provisioning_without_the_flag_is_unchanged(): void
    {
        $this->pack('partner', 'partner-basic');

        $this->provision(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'pack' => 'partner-basic'])
            ->assertCreated()
            ->assertJsonPath('user.is_trial', false)
            ->assertJsonPath('user.is_verified_partner', true)
            ->assertJsonPath('user.credits', 48);

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertFalse($user->is_trial);
        $this->assertSame(0, $user->trial_allowance);
    }

    public function test_an_ordinary_member_has_no_trial_allowance(): void
    {
        $this->provision(['name' => 'Bob Jones', 'email' => 'bob@example.com'])->assertCreated();

        $this->assertSame(0, User::where('email', 'bob@example.com')->first()->trial_allowance);
    }

    public function test_provisioning_requires_a_valid_token(): void
    {
        $this->postJson('/api/provision/user', ['name' => 'Jane', 'email' => 'jane@example.com', 'trial' => true])
            ->assertUnauthorized();

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }
}
