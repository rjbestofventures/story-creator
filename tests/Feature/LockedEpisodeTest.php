<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Episode;
use App\Models\EpisodeVersion;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LockedEpisodeTest extends TestCase
{
    use RefreshDatabase;

    private function library(User $user, int $episodes = 12): Story
    {
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create([
            'status' => 'draft',
            'episode_limit' => $episodes,
            'created_on_trial' => $user->spendsTrialAllowance(),
        ]);

        for ($i = 1; $i <= $episodes; $i++) {
            $story->episodes()->create([
                'episode_number' => $i,
                'title' => "Episode $i",
                'content' => "Secret content for episode $i",
                'format' => 'social',
                'status' => 'draft',
            ]);
        }

        return $story;
    }

    private function trialMember(): User
    {
        return User::factory()->create(['is_trial' => true, 'trial_allowance' => 1, 'credits' => 0]);
    }

    private function episode(Story $story, int $number): Episode
    {
        return $story->episodes()->where('episode_number', $number)->first();
    }

    // -------------------------------------------------------------------------
    // The rule
    // -------------------------------------------------------------------------

    public function test_episodes_beyond_the_unlocked_count_are_locked_for_a_trial_member(): void
    {
        $story = $this->library($this->trialMember());

        $this->assertFalse($this->episode($story, Story::TRIAL_UNLOCKED_EPISODES)->isLocked());
        $this->assertTrue($this->episode($story, Story::TRIAL_UNLOCKED_EPISODES + 1)->isLocked());
        $this->assertTrue($this->episode($story, 12)->isLocked());
    }

    public function test_no_episode_is_locked_for_a_member_who_is_not_in_trial(): void
    {
        $story = $this->library(User::factory()->create(['credits' => 50]));

        foreach ($story->episodes as $episode) {
            $this->assertFalse($episode->isLocked(), "Episode {$episode->episode_number} should not be locked");
        }
    }

    // -------------------------------------------------------------------------
    // Withholding
    // -------------------------------------------------------------------------

    public function test_locked_episode_content_never_reaches_the_page_payload(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->get(route('stories.show', $story))
            ->assertInertia(fn ($page) => $page
                ->component('Stories/Show')
                ->where('story.episodes.2.content', 'Secret content for episode 3')
                ->missing('story.episodes.3.content')
                ->missing('story.episodes.3.versions_count')
                ->missing('story.episodes.3.custom_refine_instruction')
                ->missing('story.episodes.11.content')
            );
    }

    public function test_locked_episodes_still_expose_their_number_and_title(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->get(route('stories.show', $story))
            ->assertInertia(fn ($page) => $page
                ->where('story.episodes.3.episode_number', 4)
                ->where('story.episodes.3.title', 'Episode 4')
                ->where('story.episodes.3.locked', true)
                ->where('story.episodes.0.locked', false)
            );
    }

    public function test_no_locked_content_appears_anywhere_in_the_response(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $response = $this->actingAs($user)->get(route('stories.show', $story));

        for ($i = Story::TRIAL_UNLOCKED_EPISODES + 1; $i <= 12; $i++) {
            $response->assertDontSee("Secret content for episode $i", false);
        }

        $response->assertSee('Secret content for episode 1', false);
    }

    public function test_a_paying_member_sees_every_episodes_content(): void
    {
        $user = User::factory()->create(['credits' => 50]);
        $story = $this->library($user);

        $this->actingAs($user)
            ->get(route('stories.show', $story))
            ->assertInertia(fn ($page) => $page
                ->where('story.episodes.11.content', 'Secret content for episode 12')
                ->where('story.episodes.11.locked', false)
            );
    }

    // -------------------------------------------------------------------------
    // Rejection
    // -------------------------------------------------------------------------

    public function test_inline_edit_refuses_a_locked_episode(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);
        $locked = $this->episode($story, 4);

        $this->actingAs($user)
            ->patch(route('stories.episode.update', [$story, $locked]), ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->assertSame('Episode 4', $locked->fresh()->title);
    }

    public function test_inline_edit_allows_an_unlocked_episode(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);
        $unlocked = $this->episode($story, 2);

        $this->actingAs($user)
            ->patch(route('stories.episode.update', [$story, $unlocked]), ['title' => 'My own words'])
            ->assertOk();

        $this->assertSame('My own words', $unlocked->fresh()->title);
    }

    public function test_saving_a_refine_instruction_refuses_a_locked_episode(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->patch(route('stories.episode.refine-instruction', [$story, $this->episode($story, 5)]), [
                'custom_refine_instruction' => 'make it snappier',
            ])
            ->assertForbidden();
    }

    public function test_ai_refine_refuses_a_locked_episode(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->post(route('stories.episode.refine', [$story, $this->episode($story, 6)]), ['tone' => 'shorter'])
            ->assertForbidden();
    }

    public function test_regenerate_refuses_a_locked_episode(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->post(route('stories.regenerate', $story), ['episode_number' => 7])
            ->assertForbidden();
    }

    public function test_listing_versions_refuses_a_locked_episode(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->get(route('stories.episode.versions', [$story, $this->episode($story, 8)]))
            ->assertForbidden();
    }

    public function test_restoring_a_version_refuses_a_locked_episode(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);
        $locked = $this->episode($story, 9);

        $version = EpisodeVersion::create([
            'episode_id' => $locked->id,
            'version' => 1,
            'title' => 'Older title',
            'content' => 'Older content',
        ]);

        $this->actingAs($user)
            ->post(route('stories.episode.restore', [$story, $locked, $version]))
            ->assertForbidden();

        $this->assertSame('Episode 9', $locked->fresh()->title);
    }

    public function test_bulk_refine_refuses_a_selection_containing_a_locked_episode(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->post(route('stories.episodes.bulk-refine', $story), [
                'episode_ids' => [$this->episode($story, 1)->id, $this->episode($story, 10)->id],
                'tone' => 'shorter',
            ])
            ->assertForbidden();

        $this->assertSame('Secret content for episode 1', $this->episode($story, 1)->fresh()->content);
    }

    public function test_playback_refuses_a_locked_episode_and_calls_no_speech_provider(): void
    {
        Http::fake();

        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->post(route('stories.episode.speak', [$story, $this->episode($story, 11)]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_playback_allows_an_unlocked_episode(): void
    {
        Http::fake(['api.openai.com/*' => Http::response('mp3-bytes', 200)]);

        $user = $this->trialMember();
        $story = $this->library($user);

        $this->actingAs($user)
            ->post(route('stories.episode.speak', [$story, $this->episode($story, 1)]))
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg');
    }

    // -------------------------------------------------------------------------
    // Existing behaviour is unchanged
    // -------------------------------------------------------------------------

    public function test_a_member_still_cannot_touch_another_members_episode(): void
    {
        $owner = User::factory()->create(['credits' => 50]);
        $story = $this->library($owner);
        $stranger = User::factory()->create(['credits' => 50]);

        $this->actingAs($stranger)
            ->patch(route('stories.episode.update', [$story, $this->episode($story, 1)]), ['title' => 'Mine now'])
            ->assertForbidden();
    }

    public function test_demo_stories_remain_read_only(): void
    {
        $user = User::factory()->create(['credits' => 50]);
        $story = $this->library($user);
        $story->update(['is_demo' => true]);

        $this->actingAs($user)
            ->patch(route('stories.episode.update', [$story, $this->episode($story, 1)]), ['title' => 'Edited'])
            ->assertForbidden();
    }

    public function test_an_episode_from_another_story_is_not_found(): void
    {
        $user = User::factory()->create(['credits' => 50]);
        $storyA = $this->library($user, 3);
        $storyB = $this->library($user, 3);

        $this->actingAs($user)
            ->patch(route('stories.episode.update', [$storyA, $this->episode($storyB, 1)]), ['title' => 'Crossed'])
            ->assertNotFound();
    }
}
