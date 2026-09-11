<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Episode;
use App\Models\EpisodeVersion;
use App\Models\Story;
use App\Models\User;
use App\Services\StoryGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RefineCreditTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // canRefine() unit tests
    // -------------------------------------------------------------------------

    public function test_can_refine_returns_true_when_credits_remain(): void
    {
        $user = User::factory()->create(['credits' => 5]);

        $this->assertTrue($user->canRefine());
    }

    public function test_can_refine_returns_false_when_credits_are_zero(): void
    {
        $user = User::factory()->create(['credits' => 0]);

        $this->assertFalse($user->canRefine());
    }

    public function test_can_refine_returns_true_for_an_admin_with_no_credits(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['credits' => 0]);
        $user->assignRole('admin');

        $this->assertTrue($user->canRefine());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function userWithCredits(int $credits): User
    {
        return User::factory()->create(['credits' => $credits]);
    }

    private function storyWithEpisode(User $user): array
    {
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create(['status' => 'draft']);
        $episode = Episode::factory()->for($story)->create([
            'episode_number' => 1,
            'title' => 'Original Title',
            'content' => 'Original content.',
            'format' => 'social',
            'status' => 'draft',
        ]);

        return [$story, $episode];
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole('admin');

        return $user;
    }

    /**
     * A trial member with no credits still reaches these controllers —
     * RequiresCredits lets is_trial through, since trial members are
     * expected to hold zero credits by design. canRefine() is what actually
     * blocks them once inside. A non-trial member with zero credits never
     * gets this far; RequiresCredits redirects them to the shop first.
     */
    private function trialMemberWithNoCredits(): User
    {
        return User::factory()->create(['is_trial' => true, 'credits' => 0]);
    }

    // -------------------------------------------------------------------------
    // regenerateEpisode — deducts one credit
    // -------------------------------------------------------------------------

    public function test_regenerate_episode_deducts_one_credit(): void
    {
        $user = $this->userWithCredits(3);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'story_title' => 'T',
                '_tokens_input' => 0,
                '_tokens_output' => 0,
                'episodes' => [['episode_number' => 1, 'title' => 'New', 'content' => 'New content.']],
            ]);

        $this->actingAs($user)
            ->postJson(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertOk();

        $this->assertSame(2, $user->fresh()->credits);
    }

    /**
     * RequiresCredits redirects a broke non-trial member before the
     * controller ever runs, so this needs a trial member (is_trial passes
     * the middleware) to actually exercise the controller's own canRefine()
     * gate. See test_regenerate_episode_redirects_a_broke_member_to_the_shop
     * for what an ordinary paying member sees at zero credits.
     */
    public function test_regenerate_episode_returns_403_when_no_credits(): void
    {
        $user = $this->trialMemberWithNoCredits();
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->actingAs($user)
            ->postJson(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertForbidden();
    }

    public function test_regenerate_episode_redirects_a_broke_member_to_the_shop(): void
    {
        $user = $this->userWithCredits(0);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->actingAs($user)
            ->post(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertRedirect(route('shop.index'));
    }

    public function test_regenerate_episode_admin_bypasses_zero_credits(): void
    {
        $user = $this->adminUser();
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'story_title' => 'T',
                '_tokens_input' => 0,
                '_tokens_output' => 0,
                'episodes' => [['episode_number' => 1, 'title' => 'New', 'content' => 'New content.']],
            ]);

        $this->actingAs($user)
            ->postJson(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertOk();

        $this->assertSame(0, $user->fresh()->credits);
    }

    // -------------------------------------------------------------------------
    // refineEpisodeTone — deducts one credit
    // -------------------------------------------------------------------------

    public function test_refine_tone_deducts_one_credit(): void
    {
        $user = $this->userWithCredits(5);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('refineTone')
            ->once()
            ->with($episode->content, 'friendlier', null)
            ->andReturn(['content' => 'Refined.', '_tokens_input' => 0, '_tokens_output' => 0]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.refine', [$story, $episode]), ['tone' => 'friendlier'])
            ->assertOk();

        $this->assertSame(4, $user->fresh()->credits);
    }

    public function test_refine_tone_passes_the_custom_instruction_through(): void
    {
        $user = $this->userWithCredits(5);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('refineTone')
            ->once()
            ->with($episode->content, 'custom', 'Make it punchier')
            ->andReturn(['content' => 'Refined.', '_tokens_input' => 0, '_tokens_output' => 0]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.refine', [$story, $episode]), [
                'tone' => 'custom',
                'custom_instruction' => 'Make it punchier',
            ])
            ->assertOk();
    }

    public function test_refine_tone_returns_403_when_no_credits(): void
    {
        $user = $this->trialMemberWithNoCredits();
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->actingAs($user)
            ->postJson(route('stories.episode.refine', [$story, $episode]), ['tone' => 'shorter'])
            ->assertForbidden();
    }

    public function test_refine_tone_redirects_a_broke_member_to_the_shop(): void
    {
        $user = $this->userWithCredits(0);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->actingAs($user)
            ->post(route('stories.episode.refine', [$story, $episode]), ['tone' => 'shorter'])
            ->assertRedirect(route('shop.index'));
    }

    public function test_refine_tone_admin_bypasses_zero_credits(): void
    {
        $user = $this->adminUser();
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('refineTone')
            ->once()
            ->andReturn(['content' => 'Refined.', '_tokens_input' => 0, '_tokens_output' => 0]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.refine', [$story, $episode]), ['tone' => 'professional'])
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // restoreVersion — free. Restoring what was already generated is not a
    // new generation, so it never touches credits and never gates on them.
    // -------------------------------------------------------------------------

    public function test_restore_version_does_not_touch_credits(): void
    {
        $user = $this->userWithCredits(4);
        [$story, $episode] = $this->storyWithEpisode($user);

        $version = EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version' => 1,
            'title' => 'v1 Title',
            'content' => 'v1 content.',
        ]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.restore', [$story, $episode, $version]))
            ->assertOk();

        $this->assertSame(4, $user->fresh()->credits);
    }

    public function test_restore_version_works_for_a_trial_member_with_zero_credits(): void
    {
        $user = $this->trialMemberWithNoCredits();
        [$story, $episode] = $this->storyWithEpisode($user);

        $version = EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version' => 1,
            'title' => 'v1 Title',
            'content' => 'v1 content.',
        ]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.restore', [$story, $episode, $version]))
            ->assertOk();

        $this->assertSame(0, $user->fresh()->credits);
    }

    /**
     * restoreVersion itself never checks credits — but RequiresCredits sits
     * in front of it along with every other write route on a story, so a
     * broke non-trial member is redirected to the shop before ever reaching
     * the free logic inside. Restoring is free in name only for that member.
     */
    public function test_restore_version_redirects_a_broke_non_trial_member_to_the_shop(): void
    {
        $user = $this->userWithCredits(0);
        [$story, $episode] = $this->storyWithEpisode($user);

        $version = EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version' => 1,
            'title' => 'v1 Title',
            'content' => 'v1 content.',
        ]);

        $this->actingAs($user)
            ->post(route('stories.episode.restore', [$story, $episode, $version]))
            ->assertRedirect(route('shop.index'));
    }

    // -------------------------------------------------------------------------
    // Credit hits zero exactly — boundary condition
    // -------------------------------------------------------------------------

    public function test_last_credit_is_consumed_and_the_next_regenerate_is_blocked(): void
    {
        $user = $this->userWithCredits(1);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'story_title' => 'T',
                '_tokens_input' => 0,
                '_tokens_output' => 0,
                'episodes' => [['episode_number' => 1, 'title' => 'New', 'content' => 'New content.']],
            ]);

        // First call — consumes the last credit.
        $this->actingAs($user)
            ->postJson(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertOk();

        $this->assertSame(0, $user->fresh()->credits);

        // Second call — a non-trial member at zero credits is caught by
        // RequiresCredits before the controller's own canRefine() check would
        // even run.
        $this->actingAs($user->fresh())
            ->post(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertRedirect(route('shop.index'));
    }
}
