<?php

namespace Tests\Feature;

use App\Jobs\GenerateStory;
use App\Models\BusinessProfile;
use App\Models\Plan;
use App\Models\Story;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\StoryGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StoryGenerationEpisodeCountTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGenerator(): StoryGeneratorService
    {
        $mock = Mockery::mock(StoryGeneratorService::class);
        $mock->shouldReceive('generate')
            ->once()
            ->andReturnUsing(function ($profile, int $count, string $format) {
                $episodes = [];
                for ($i = 1; $i <= $count; $i++) {
                    $episodes[] = [
                        'episode_number' => $i,
                        'title' => "Episode $i",
                        'content' => "Content for episode $i",
                    ];
                }

                return [
                    'story_title' => 'Test Story',
                    '_tokens_input' => 0,
                    '_tokens_output' => 0,
                    'episodes' => $episodes,
                ];
            });

        return $mock;
    }

    public function test_job_creates_episode_count_matching_plan_limit(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['episode_limit' => 7]);
        UserSubscription::factory()->for($user)->for($plan)->create(['episode_limit' => 0]);
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create(['status' => 'generating']);

        (new GenerateStory($story, 'social'))->handle($this->fakeGenerator());

        $this->assertSame(7, $story->fresh()->episodes()->count());
    }

    public function test_job_uses_subscription_override_when_set(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['episode_limit' => 5]);
        UserSubscription::factory()->for($user)->for($plan)->create(['episode_limit' => 15]);
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create(['status' => 'generating']);

        (new GenerateStory($story, 'social'))->handle($this->fakeGenerator());

        $this->assertSame(15, $story->fresh()->episodes()->count());
    }

    public function test_demo_story_always_generates_3_episodes(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['episode_limit' => 10]);
        UserSubscription::factory()->for($user)->for($plan)->create();
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create([
            'status' => 'generating',
            'is_demo' => true,
        ]);

        $mock = Mockery::mock(StoryGeneratorService::class);
        $mock->shouldReceive('generate')
            ->once()
            ->with(Mockery::any(), 3, Mockery::any())
            ->andReturn([
                'story_title' => 'Demo Story',
                '_tokens_input' => 0,
                '_tokens_output' => 0,
                'episodes' => [
                    ['episode_number' => 1, 'title' => 'Episode 1', 'content' => 'Content 1'],
                    ['episode_number' => 2, 'title' => 'Episode 2', 'content' => 'Content 2'],
                    ['episode_number' => 3, 'title' => 'Episode 3', 'content' => 'Content 3'],
                ],
            ]);

        (new GenerateStory($story, 'social'))->handle($mock);

        $this->assertSame(3, $story->fresh()->episodes()->count());
    }

    public function test_job_marks_story_failed_when_generator_throws(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['episode_limit' => 5]);
        UserSubscription::factory()->for($user)->for($plan)->create();
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create(['status' => 'generating']);

        $mock = Mockery::mock(StoryGeneratorService::class);
        $mock->shouldReceive('generate')->once()->andThrow(new \RuntimeException('API error'));

        try {
            (new GenerateStory($story, 'social'))->handle($mock);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException) {
            // expected — job rethrows after marking failed
        }

        $this->assertSame('failed', $story->fresh()->status);
    }

    public function test_generate_endpoint_runs_job_and_creates_episodes_via_sync_queue(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['episode_limit' => 5]);
        UserSubscription::factory()->for($user)->for($plan)->create(['story_credits' => 3]);
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create(['status' => 'interview_complete']);

        $this->mock(StoryGeneratorService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'story_title' => 'Generated Story',
                '_tokens_input' => 0,
                '_tokens_output' => 0,
                'episodes' => [
                    ['episode_number' => 1, 'title' => 'Episode 1', 'content' => 'Content 1'],
                    ['episode_number' => 2, 'title' => 'Episode 2', 'content' => 'Content 2'],
                    ['episode_number' => 3, 'title' => 'Episode 3', 'content' => 'Content 3'],
                    ['episode_number' => 4, 'title' => 'Episode 4', 'content' => 'Content 4'],
                    ['episode_number' => 5, 'title' => 'Episode 5', 'content' => 'Content 5'],
                ],
            ]);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social'])
            ->assertRedirect(route('stories.show', $story));

        $this->assertSame(5, $story->fresh()->episodes()->count());
        $this->assertSame('draft', $story->fresh()->status);
    }
}
