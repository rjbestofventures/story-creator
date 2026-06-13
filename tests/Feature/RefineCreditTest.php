<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Episode;
use App\Models\EpisodeVersion;
use App\Models\Plan;
use App\Models\Story;
use App\Models\User;
use App\Models\UserSubscription;
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

    public function test_can_refine_returns_true_when_active_and_credits_remain(): void
    {
        $plan = Plan::factory()->create();
        $sub  = UserSubscription::factory()->for($plan)->create([
            'status'         => 'active',
            'refine_credits' => 5,
        ]);

        $this->assertTrue($sub->canRefine());
    }

    public function test_can_refine_returns_false_when_credits_are_zero(): void
    {
        $plan = Plan::factory()->create();
        $sub  = UserSubscription::factory()->for($plan)->create([
            'status'         => 'active',
            'refine_credits' => 0,
        ]);

        $this->assertFalse($sub->canRefine());
    }

    public function test_can_refine_returns_false_when_subscription_is_cancelled(): void
    {
        $plan = Plan::factory()->create();
        $sub  = UserSubscription::factory()->for($plan)->create([
            'status'         => 'cancelled',
            'refine_credits' => 10,
        ]);

        $this->assertFalse($sub->canRefine());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function userWithCredits(int $refineCredits): User
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        UserSubscription::factory()->for($user)->for($plan)->create([
            'refine_credits' => $refineCredits,
        ]);

        return $user;
    }

    private function storyWithEpisode(User $user): array
    {
        $profile = BusinessProfile::factory()->for($user)->create();
        $story   = Story::factory()->for($user)->for($profile)->create(['status' => 'draft']);
        $episode = Episode::factory()->for($story)->create([
            'episode_number' => 1,
            'title'          => 'Original Title',
            'content'        => 'Original content.',
            'format'         => 'social',
            'status'         => 'draft',
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

    // -------------------------------------------------------------------------
    // regenerateEpisode — deducts one refine credit
    // -------------------------------------------------------------------------

    public function test_regenerate_episode_deducts_one_refine_credit(): void
    {
        $user = $this->userWithCredits(3);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'story_title'     => 'T',
                '_tokens_input'   => 0,
                '_tokens_output'  => 0,
                'episodes'        => [['episode_number' => 1, 'title' => 'New', 'content' => 'New content.']],
            ]);

        $this->actingAs($user)
            ->postJson(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertOk();

        $this->assertSame(2, $user->activeSubscription->fresh()->refine_credits);
    }

    public function test_regenerate_episode_returns_403_when_no_refine_credits(): void
    {
        $user = $this->userWithCredits(0);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->actingAs($user)
            ->postJson(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertForbidden();
    }

    public function test_regenerate_episode_admin_bypasses_zero_credits(): void
    {
        $user = $this->adminUser();
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'story_title'     => 'T',
                '_tokens_input'   => 0,
                '_tokens_output'  => 0,
                'episodes'        => [['episode_number' => 1, 'title' => 'New', 'content' => 'New content.']],
            ]);

        $this->actingAs($user)
            ->postJson(route('stories.regenerate', $story), ['episode_number' => 1])
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // refineEpisodeTone — deducts one refine credit
    // -------------------------------------------------------------------------

    public function test_refine_tone_deducts_one_refine_credit(): void
    {
        $user = $this->userWithCredits(5);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('refineTone')
            ->once()
            ->andReturn(['content' => 'Refined.', '_tokens_input' => 0, '_tokens_output' => 0]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.refine', [$story, $episode]), ['tone' => 'friendlier'])
            ->assertOk();

        $this->assertSame(4, $user->activeSubscription->fresh()->refine_credits);
    }

    public function test_refine_tone_returns_403_when_no_refine_credits(): void
    {
        $user = $this->userWithCredits(0);
        [$story, $episode] = $this->storyWithEpisode($user);

        $this->actingAs($user)
            ->postJson(route('stories.episode.refine', [$story, $episode]), ['tone' => 'shorter'])
            ->assertForbidden();
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
    // restoreVersion — deducts one refine credit
    // -------------------------------------------------------------------------

    public function test_restore_version_deducts_one_refine_credit(): void
    {
        $user = $this->userWithCredits(4);
        [$story, $episode] = $this->storyWithEpisode($user);

        $version = EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version'    => 1,
            'title'      => 'v1 Title',
            'content'    => 'v1 content.',
        ]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.restore', [$story, $episode, $version]))
            ->assertOk();

        $this->assertSame(3, $user->activeSubscription->fresh()->refine_credits);
    }

    public function test_restore_version_returns_403_when_no_refine_credits(): void
    {
        $user = $this->userWithCredits(0);
        [$story, $episode] = $this->storyWithEpisode($user);

        $version = EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version'    => 1,
            'title'      => 'v1 Title',
            'content'    => 'v1 content.',
        ]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.restore', [$story, $episode, $version]))
            ->assertForbidden();
    }

    public function test_restore_version_admin_bypasses_zero_credits(): void
    {
        $user = $this->adminUser();
        [$story, $episode] = $this->storyWithEpisode($user);

        $version = EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version'    => 1,
            'title'      => 'v1 Title',
            'content'    => 'v1 content.',
        ]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.restore', [$story, $episode, $version]))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // Credit hits zero exactly — boundary condition
    // -------------------------------------------------------------------------

    public function test_last_refine_credit_is_consumed_and_next_call_is_blocked(): void
    {
        $user = $this->userWithCredits(1);
        [$story, $episode] = $this->storyWithEpisode($user);

        $version = EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version'    => 1,
            'title'      => 'v1',
            'content'    => 'v1.',
        ]);

        // First call — consumes the last credit
        $this->actingAs($user)
            ->postJson(route('stories.episode.restore', [$story, $episode, $version]))
            ->assertOk();

        $this->assertSame(0, $user->activeSubscription->fresh()->refine_credits);

        // Second call — must be blocked
        $version2 = EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version'    => 2,
            'title'      => 'v2',
            'content'    => 'v2.',
        ]);

        $this->actingAs($user)
            ->postJson(route('stories.episode.restore', [$story, $episode, $version2]))
            ->assertForbidden();
    }
}
