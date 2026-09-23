<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminVbpManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findOrCreate('admin'));
    }

    public function test_the_users_page_shows_temporary_vbp_and_plan(): void
    {
        $temp = User::factory()->create(['credits' => 0]);
        $temp->becomeTemporaryPartner();
        User::factory()->create(['is_verified_partner' => true, 'vbp_plan' => 'gold']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertInertia(fn ($page) => $page
                ->where('users', fn ($users) => collect($users)->contains(fn ($u) => $u['email'] === $temp->email
                        && $u['is_temporary_vbp'] === true
                        && $u['temporary_vbp_expires_at'] === now()->addMonths(3)->toDateString()
                        && $u['temporary_story_used'] === false)
                    && collect($users)->contains(fn ($u) => $u['vbp_plan'] === 'gold'))
            );
    }

    public function test_making_a_member_a_temporary_vbp_and_back(): void
    {
        $user = User::factory()->create(['credits' => 0]);

        $this->actingAs($this->admin)->post(route('admin.users.toggle-temporary-vbp', $user))->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->is_temporary_vbp);
        $this->assertSame(12, $user->credits);
        $this->assertNotNull($user->temporary_vbp_expires_at);

        $this->actingAs($this->admin)->post(route('admin.users.toggle-temporary-vbp', $user))->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->is_temporary_vbp);
        $this->assertNull($user->temporary_vbp_expires_at);
        $this->assertSame(12, $user->credits);
    }

    public function test_a_full_partner_cannot_be_made_temporary(): void
    {
        $partner = User::factory()->create(['is_verified_partner' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle-temporary-vbp', $partner))
            ->assertStatus(422);

        $this->assertFalse($partner->fresh()->is_temporary_vbp);
    }

    public function test_extending_the_expiry_reopens_an_expired_account(): void
    {
        $user = User::factory()->create(['credits' => 0]);
        $user->becomeTemporaryPartner();
        $user->update(['is_active' => false]);

        $date = now()->addMonth()->toDateString();

        $this->actingAs($this->admin)
            ->post(route('admin.users.temporary-vbp-expiry', $user), ['temporary_vbp_expires_at' => $date])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertSame($date, $user->temporary_vbp_expires_at->toDateString());
    }

    public function test_setting_a_plan_converts_a_temporary_vbp(): void
    {
        $user = User::factory()->create(['credits' => 0]);
        $user->becomeTemporaryPartner();

        $this->actingAs($this->admin)
            ->post(route('admin.users.vbp-plan', $user), ['vbp_plan' => 'silver'])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->is_verified_partner);
        $this->assertFalse($user->is_temporary_vbp);
        $this->assertSame('silver', $user->vbp_plan);
        $this->assertSame(12 + 36, $user->credits);
    }

    public function test_changing_an_existing_partners_plan_grants_nothing(): void
    {
        $partner = User::factory()->create(['is_verified_partner' => true, 'vbp_plan' => 'silver', 'credits' => 10]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.vbp-plan', $partner), ['vbp_plan' => 'gold'])
            ->assertRedirect();

        $partner->refresh();
        $this->assertSame('gold', $partner->vbp_plan);
        $this->assertSame(10, $partner->credits);
    }
}
