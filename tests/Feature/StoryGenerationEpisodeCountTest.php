<?php

namespace Tests\Feature;

use App\Jobs\GenerateStory;
use App\Models\BusinessProfile;
use App\Models\Story;
use App\Models\User;
use App\Services\StoryGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StoryGenerationEpisodeCountTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGenerator(?int $expectedCount = null): StoryGeneratorService
    {
        $mock = Mockery::mock(StoryGeneratorService::class);
        $expectation = $mock->shouldReceive('generate')->once();

        if ($expectedCount !== null) {
            $expectation->with(Mockery::any(), $expectedCount, Mockery::any());
        }

        $expectation->andReturnUsing(function ($profile, int $count, string $format) {
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

    private function story(User $user, array $attributes = []): Story
    {
        $profile = BusinessProfile::factory()->for($user)->create();

        return Story::factory()->for($user)->for($profile)->create($attributes + ['status' => 'generating']);
    }

    public function test_job_creates_episode_count_matching_the_stories_episode_limit(): void
    {
        $user = User::factory()->create();
        $story = $this->story($user, ['episode_limit' => 18]);

        (new GenerateStory($story, 'social'))->handle($this->fakeGenerator(18));

        $this->assertSame(18, $story->fresh()->episodes()->count());
    }

    public function test_job_falls_back_to_12_episodes_when_no_limit_is_stamped(): void
    {
        $user = User::factory()->create();
        $story = $this->story($user, ['episode_limit' => null]);

        (new GenerateStory($story, 'social'))->handle($this->fakeGenerator(12));

        $this->assertSame(12, $story->fresh()->episodes()->count());
    }

    public function test_demo_story_always_generates_3_episodes(): void
    {
        $user = User::factory()->create();
        $story = $this->story($user, ['is_demo' => true, 'episode_limit' => 24]);

        (new GenerateStory($story, 'social'))->handle($this->fakeGenerator(3));

        $this->assertSame(3, $story->fresh()->episodes()->count());
    }

    public function test_job_marks_story_failed_when_generator_throws(): void
    {
        $user = User::factory()->create();
        $story = $this->story($user, ['episode_limit' => 12]);

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
        $user = User::factory()->create(['credits' => 12]);
        $story = $this->story($user, ['status' => 'interview_complete']);

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
                ],
            ]);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social', 'episode_count' => 12])
            ->assertRedirect(route('stories.show', $story));

        $this->assertSame(2, $story->fresh()->episodes()->count());
        $this->assertSame(0, $user->fresh()->credits);
    }
}
