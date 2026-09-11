<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\SiteSetting;
use App\Models\Story;
use App\Models\User;
use App\Notifications\EpisodesReactivatedNotification;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UnlockAndReactivateEpisodesTest extends TestCase
{
    use RefreshDatabase;

    private function trialLibrary(int $credits = 0, ?CarbonInterface $joined = null): Story
    {
        $user = User::factory()->create([
            'is_trial' => true,
            'trial_allowance' => 0,
            'credits' => $credits,
            'created_at' => $joined ?? now(),
        ]);

        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create([
            'status' => 'draft',
            'episode_limit' => Story::TRIAL_EPISODE_COUNT,
            'created_on_trial' => true,
        ]);

        for ($i = 1; $i <= Story::TRIAL_EPISODE_COUNT; $i++) {
            $story->episodes()->create([
                'episode_number' => $i,
                'title' => "Episode $i",
                'content' => "Content $i",
                'format' => 'social',
                'status' => 'draft',
            ]);
        }

        return $story->fresh(['episodes', 'user']);
    }

    public function test_unlock_cost_is_the_number_of_withheld_episodes(): void
    {
        $story = $this->trialLibrary();

        $this->assertSame(9, $story->unlockCost());
    }

    public function test_paying_the_cost_opens_the_library_and_debits_the_wallet(): void
    {
        $story = $this->trialLibrary(credits: 45);

        $this->actingAs($story->user)
            ->post(route('stories.unlock', $story))
            ->assertRedirect();

        $story = $story->fresh(['episodes', 'user']);

        $this->assertNotNull($story->episodes_unlocked_at);
        $this->assertFalse($story->locksEpisodes());
        $this->assertFalse($story->episodes->last()->isLocked());
        $this->assertSame(36, $story->user->credits);
    }

    public function test_it_refuses_when_the_wallet_is_short(): void
    {
        $story = $this->trialLibrary(credits: 3);

        $this->actingAs($story->user)
            ->post(route('stories.unlock', $story))
            ->assertForbidden();

        $story = $story->fresh(['episodes', 'user']);

        $this->assertNull($story->episodes_unlocked_at);
        $this->assertSame(3, $story->user->credits);
    }

    public function test_a_library_is_hidden_a_month_after_the_member_joined(): void
    {
        $fresh = $this->trialLibrary(joined: now()->subDays(5));
        $this->assertFalse($fresh->hidesReadableEpisodes());

        $stale = $this->trialLibrary(joined: now()->subDays(40));
        $this->assertTrue($stale->hidesReadableEpisodes());
    }

    public function test_reactivating_brings_the_library_back(): void
    {
        $story = $this->trialLibrary(joined: now()->subDays(40));

        $this->actingAs($story->user)
            ->post(route('stories.reactivate', $story))
            ->assertRedirect();

        $story = $story->fresh(['episodes', 'user']);

        $this->assertNotNull($story->episodes_reactivated_at);
        $this->assertFalse($story->hidesReadableEpisodes());
    }

    public function test_the_team_is_told_a_day_after_a_reactivation(): void
    {
        Notification::fake();
        SiteSetting::set('admin_notification_email', 'team@example.com');

        $story = $this->trialLibrary(joined: now()->subDays(40));
        $story->forceFill(['episodes_reactivated_at' => now()->subHours(2)])->save();

        $this->artisan('trial:notify-reactivations')->assertExitCode(0);
        Notification::assertNothingSent();

        $story->forceFill(['episodes_reactivated_at' => now()->subHours(25)])->save();

        $this->artisan('trial:notify-reactivations')->assertExitCode(0);
        Notification::assertSentOnDemand(EpisodesReactivatedNotification::class);

        $this->assertNotNull($story->fresh()->reactivation_notified_at);
    }

    public function test_becoming_a_partner_ends_the_trial_but_leaves_the_library_shut(): void
    {
        $story = $this->trialLibrary();
        $user = $story->user;

        $user->becomePartner();

        $user->refresh();
        $story = $story->fresh(['episodes', 'user']);

        $this->assertTrue($user->is_verified_partner);
        $this->assertFalse($user->is_trial);
        $this->assertSame(0, $user->trial_allowance);
        $this->assertTrue($story->locksEpisodes());
        $this->assertTrue($story->episodes->last()->isLocked());
    }

    public function test_ending_a_trial_by_paying_opens_the_library(): void
    {
        $story = $this->trialLibrary();

        $story->user->endTrial();

        $story = $story->fresh(['episodes', 'user']);

        $this->assertFalse($story->user->is_trial);
        $this->assertFalse($story->locksEpisodes());
        $this->assertFalse($story->episodes->last()->isLocked());
    }

    public function test_a_library_not_made_on_trial_never_locks(): void
    {
        $story = $this->trialLibrary();
        $story->forceFill(['created_on_trial' => false])->save();

        $this->assertFalse($story->fresh(['episodes', 'user'])->locksEpisodes());
    }

    public function test_the_team_is_not_told_twice(): void
    {
        Notification::fake();

        $story = $this->trialLibrary(joined: now()->subDays(40));
        $story->forceFill(['episodes_reactivated_at' => now()->subHours(25)])->save();

        $this->artisan('trial:notify-reactivations');
        $this->artisan('trial:notify-reactivations');

        Notification::assertSentOnDemandTimes(EpisodesReactivatedNotification::class, 1);
    }
}
