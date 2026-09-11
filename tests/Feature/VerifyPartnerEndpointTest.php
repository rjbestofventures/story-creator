<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VerifyPartnerEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.provision_api_token' => 'test-token']);
    }

    private function verify(string $email): TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/provision/verify-partner', ['email' => $email]);
    }

    public function test_it_marks_an_existing_member_as_a_verified_partner(): void
    {
        $user = User::factory()->create(['is_verified_partner' => false]);

        $this->verify($user->email)
            ->assertOk()
            ->assertJsonPath('user.is_verified_partner', true);

        $this->assertTrue($user->fresh()->is_verified_partner);
    }

    public function test_it_is_safe_to_repeat(): void
    {
        $user = User::factory()->create(['is_verified_partner' => false]);

        $this->verify($user->email)->assertOk();
        $this->verify($user->email)->assertOk();

        $this->assertTrue($user->fresh()->is_verified_partner);
    }

    public function test_it_leaves_a_trial_running_and_grants_nothing(): void
    {
        $user = User::factory()->create([
            'is_trial' => true,
            'trial_allowance' => 1,
            'credits' => 0,
        ]);

        $this->verify($user->email)->assertOk()->assertJsonPath('user.is_trial', true);

        $user->refresh();

        $this->assertTrue($user->is_trial);
        $this->assertTrue($user->is_verified_partner);
        $this->assertSame(1, $user->trial_allowance);
        $this->assertSame(0, $user->credits);
        $this->assertSame(0, $user->purchases()->count());
    }

    public function test_it_requires_a_valid_token(): void
    {
        $user = User::factory()->create(['is_verified_partner' => false]);

        $this->postJson('/api/provision/verify-partner', ['email' => $user->email])
            ->assertUnauthorized();

        $this->assertFalse($user->fresh()->is_verified_partner);
    }
}
