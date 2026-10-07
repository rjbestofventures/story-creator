<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReactivateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_email_a_user_a_fresh_password(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $member = User::factory()->create(['name' => 'Jane Doe']);
        $oldPassword = $member->password;

        $this->actingAs($admin)
            ->post(route('admin.users.reactivate', $member))
            ->assertRedirect();

        $this->assertNotSame($oldPassword, $member->fresh()->password);
        Notification::assertSentTo($member, AccountCreatedNotification::class);
    }

    public function test_a_regular_user_cannot_reactivate_anyone(): void
    {
        Notification::fake();

        $member = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.users.reactivate', $member))
            ->assertForbidden();

        Notification::assertNothingSent();
    }
}
