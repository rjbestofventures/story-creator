<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTrialVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** Backdated so the members under test sort ahead of it in the list. */
    private function admin(): User
    {
        $admin = User::factory()->create(['created_at' => now()->subDay()]);
        $admin->assignRole(Role::findOrCreate('admin'));

        return $admin;
    }

    public function test_the_user_list_reports_trial_state_and_remaining_allowance(): void
    {
        $admin = $this->admin();
        User::factory()->create([
            'name' => 'Trial Lead',
            'is_trial' => true,
            'trial_allowance' => 1,
            'is_verified_partner' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users')
                ->where('users.0.is_trial', true)
                ->where('users.0.trial_allowance', 1)
                ->where('users.0.is_verified_partner', false)
            );
    }

    public function test_trial_state_and_partner_state_are_reported_separately(): void
    {
        $admin = $this->admin();
        User::factory()->create(['is_trial' => true, 'trial_allowance' => 1, 'is_verified_partner' => true]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertInertia(fn ($page) => $page
                ->where('users.0.is_trial', true)
                ->where('users.0.is_verified_partner', true)
            );
    }

    public function test_an_admin_can_set_a_trial_allowance(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['is_trial' => true, 'trial_allowance' => 0, 'credits' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.users.trial-allowance', $user), ['trial_allowance' => 2])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame(2, $user->trial_allowance);
        $this->assertTrue($user->is_trial);
    }

    public function test_setting_the_allowance_to_zero_ends_the_trial(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['is_trial' => true, 'trial_allowance' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.users.trial-allowance', $user), ['trial_allowance' => 0])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->is_trial);
    }

    public function test_setting_the_allowance_grants_no_credits_and_unlocks_nothing(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['is_trial' => true, 'trial_allowance' => 0, 'credits' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.users.trial-allowance', $user), ['trial_allowance' => 3]);

        $user->refresh();

        $this->assertSame(0, $user->credits);
        $this->assertSame(0, $user->purchases()->count());
    }

    public function test_a_non_admin_cannot_set_a_trial_allowance(): void
    {
        $user = User::factory()->create(['credits' => 10]);
        $target = User::factory()->create(['trial_allowance' => 0]);

        $this->actingAs($user)
            ->post(route('admin.users.trial-allowance', $target), ['trial_allowance' => 5])
            ->assertForbidden();

        $this->assertSame(0, $target->fresh()->trial_allowance);
    }
}
