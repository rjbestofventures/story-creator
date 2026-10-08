<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SetPasswordReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReactivateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_remind_a_user_to_set_their_password(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $member = User::factory()->create(['name' => 'Jane Doe']);
        $password = $member->password;

        $this->actingAs($admin)
            ->post(route('admin.users.reactivate', $member))
            ->assertRedirect();

        $this->assertSame($password, $member->fresh()->password);
        Notification::assertSentTo($member, SetPasswordReminderNotification::class);
    }

    public function test_the_reminder_follows_the_approved_copy(): void
    {
        $member = User::factory()->create(['email' => 'jane@example.com']);

        $mail = (new SetPasswordReminderNotification('tok'))->toMail($member);
        $html = html_entity_decode((string) $mail->render(), ENT_QUOTES);

        $this->assertSame('StoryCreator.Bot Launch Reminder', $mail->subject);

        foreach ([
            'images/best-of-delray-beach-logo.png',
            'ATTENTION!',
            'To our much appreciated and loyal Verified Business Partners,',
            "Before you can start creating great posts about your business, you'll need to set your password. We can’t wait to see the exciting outcome of your story.",
            'Click below to get going:',
            '>Set Your Password</a>',
            "Once you've set your password, you're in! Log in anytime with your email and start creating stories that bring your business to life on social media.",
            'Regards,<br>Best of Delray Beach',
            'If you\'re having trouble clicking the "Set Your Password" button, copy and paste the URL below into your web browser:',
            '/reset-password/tok?email=jane%40example.com',
        ] as $copy) {
            $this->assertStringContainsString($copy, $html);
        }
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
