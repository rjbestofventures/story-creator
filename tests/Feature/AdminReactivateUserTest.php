<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountReactivationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReactivateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_email_a_user_a_new_setup_link(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $member = User::factory()->create(['name' => 'Jane Doe']);

        $this->actingAs($admin)
            ->post(route('admin.users.reactivate', $member))
            ->assertRedirect();

        Notification::assertSentTo($member, AccountReactivationNotification::class);
    }

    public function test_the_email_follows_the_approved_copy(): void
    {
        $member = User::factory()->create(['name' => 'Jane Doe']);

        $mail = (new AccountReactivationNotification('tok'))->toMail($member);
        $html = (string) $mail->render();

        $this->assertSame('Your StoryBot account is waiting for you', $mail->subject);
        $this->assertSame('Hi Jane,', $mail->greeting);
        $this->assertStringContainsString('Set Up My Account', $html);
        $this->assertStringContainsString('/reset-password/tok', $html);
        $this->assertStringContainsString('The StoryBot Team', $html);
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
