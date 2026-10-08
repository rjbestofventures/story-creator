<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    private function sendWelcomeTo(User $user): string
    {
        Notification::fake();

        $user->sendWelcomeEmail();

        $html = null;
        Notification::assertSentTo($user, AccountCreatedNotification::class, function ($notification) use ($user, &$html) {
            $html = (string) $notification->toMail($user)->render();

            return true;
        });

        return $html;
    }

    private function passwordIn(string $html): string
    {
        preg_match('/Temporary Password:.*?<span[^>]*>([^<]+)<\/span>/s', $html, $m);

        return $m[1];
    }

    private function linkIn(string $html, string $text): string
    {
        preg_match('/<a href="([^"]+)"[^>]*>'.preg_quote($text, '/').'<\/a>/', $html, $m);

        return html_entity_decode($m[1]);
    }

    public function test_the_email_carries_a_generated_password_that_signs_them_in(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'password_set_at' => now()]);

        $html = $this->sendWelcomeTo($user);
        $password = $this->passwordIn($html);

        $this->assertSame(12, strlen($password));
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
        $this->assertNull($user->fresh()->password_set_at);
        $this->assertStringContainsString('jane@example.com', $html);
    }

    public function test_the_email_follows_the_approved_copy(): void
    {
        $html = $this->sendWelcomeTo(User::factory()->create());

        foreach ([
            'BEST OF DELRAY BEACH',
            'Welcome! Start enjoying Best of Benefits!',
            'Your enhanced business profile will appear on the Best of Local App within 48 hrs.',
            'Look for Best of Newsletter. Where your feedback and everything you need to know about getting top performance from your social media posts, will be delivered monthly.',
            'Your StoryCreator.Bot account, the fastest and easiest way to make social media content, has been launched! Here is the account information and login details to get you started.',
            'Temporary Password:',
            'Log In Now',
            'This button logs you in automatically. You can also log in anytime with the email and password above.',
            'The password above is temporary. You can reset it anytime from your account dashboard.',
            'to reset your password.',
            'Regards,<br>Best of Delray Beach',
        ] as $copy) {
            $this->assertStringContainsString($copy, html_entity_decode($html, ENT_QUOTES));
        }
    }

    public function test_log_in_now_signs_them_in_and_opens_their_stories(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole(Role::findOrCreate('user'));

        $url = $this->linkIn($this->sendWelcomeTo($user), 'Log In Now');

        $this->get($url)->assertRedirect(route('stories.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame(1, $user->fresh()->login_count);
    }

    public function test_this_link_signs_them_in_and_opens_their_profile(): void
    {
        $user = User::factory()->create();

        $url = $this->linkIn($this->sendWelcomeTo($user), 'this link');

        $this->get($url)->assertRedirect(route('profile.edit'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_link_stops_working_once_the_password_changes(): void
    {
        $user = User::factory()->create();
        $url = $user->loginLink('stories');

        $user->forceFill(['password' => 'a-new-password'])->save();

        $this->get($url)->assertForbidden();
        $this->assertGuest();
    }

    public function test_a_tampered_or_expired_link_is_refused(): void
    {
        $user = User::factory()->create();

        $this->get($user->loginLink('stories').'x')->assertForbidden();

        $url = $user->loginLink('stories');
        $this->travel(User::LOGIN_LINK_DAYS + 1)->days();
        $this->get($url)->assertForbidden();

        $this->assertGuest();
    }

    public function test_a_deactivated_account_is_not_signed_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->get($user->loginLink('stories'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
