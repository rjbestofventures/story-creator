<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiUserTrialAllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('user');
        Role::findOrCreate('admin');
    }

    private function createUser(array $payload): TestResponse
    {
        return $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/users', $payload);
    }

    public function test_trial_allowance_param_puts_the_new_account_into_trial(): void
    {
        $this->createUser([
            'name' => 'Trial Member',
            'email' => 'trial@example.com',
            'trial_allowance' => 1,
        ])->assertCreated()
            ->assertJsonPath('is_trial', true)
            ->assertJsonPath('trial_allowance', 1);

        $user = User::where('email', 'trial@example.com')->firstOrFail();
        $this->assertTrue($user->is_trial);
        $this->assertSame(1, $user->trial_allowance);
    }

    public function test_omitting_trial_allowance_creates_a_plain_account(): void
    {
        $this->createUser([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
        ])->assertCreated()
            ->assertJsonPath('is_trial', false)
            ->assertJsonPath('trial_allowance', 0);

        $user = User::where('email', 'plain@example.com')->firstOrFail();
        $this->assertFalse($user->is_trial);
        $this->assertSame(0, $user->trial_allowance);
    }

    public function test_trial_allowance_must_be_a_non_negative_integer(): void
    {
        $this->createUser([
            'name' => 'Bad Allowance',
            'email' => 'bad@example.com',
            'trial_allowance' => -1,
        ])->assertJsonValidationErrors('trial_allowance');
    }
}
