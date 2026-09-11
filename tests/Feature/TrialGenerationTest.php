<?php

namespace Tests\Feature;

use App\Jobs\GenerateStory;
use App\Models\BusinessProfile;
use App\Models\Story;
use App\Models\User;
use App\Services\StoryGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class TrialGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function trialMember(int $allowance = 1): User
    {
        return User::factory()->create([
            'is_trial' => true,
            'trial_allowance' => $allowance,
            'credits' => 0,
        ]);
    }

    private function story(User $user, string $status = 'interview_complete'): Story
    {
        $profile = BusinessProfile::factory()->for($user)->create();

        return Story::factory()->for($user)->for($profile)->create([
            'status' => $status,
            'created_on_trial' => $user->spendsTrialAllowance(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Reaching the interview without credits
    // -------------------------------------------------------------------------

    public function test_a_trial_member_reaches_the_interview_without_credits(): void
    {
        $this->actingAs($this->trialMember())
            ->get(route('stories.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Stories/Create')
                ->where('is_trial', true)
                ->where('trial_episode_count', Story::TRIAL_EPISODE_COUNT)
            );
    }

    public function test_a_member_with_no_credits_and_no_trial_is_still_sent_to_the_shop(): void
    {
        $this->actingAs(User::factory()->create(['credits' => 0]))
            ->get(route('stories.create'))
            ->assertRedirect(route('shop.index'));
    }

    // -------------------------------------------------------------------------
    // Generating
    // -------------------------------------------------------------------------

    public function test_generation_spends_trial_allowance_rather_than_credits(): void
    {
        Queue::fake();

        $user = $this->trialMember();
        $story = $this->story($user);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social'])
            ->assertRedirect(route('stories.show', $story));

        $user->refresh();

        $this->assertSame(0, $user->trial_allowance);
        $this->assertSame(0, $user->credits);
        Queue::assertPushed(GenerateStory::class);
    }

    public function test_a_trial_library_is_always_the_fixed_size(): void
    {
        Queue::fake();

        $user = $this->trialMember();
        $story = $this->story($user);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social', 'episode_count' => 24])
            ->assertRedirect(route('stories.show', $story));

        $this->assertSame(Story::TRIAL_EPISODE_COUNT, $story->fresh()->episode_limit);
    }

    public function test_generation_is_refused_once_the_allowance_is_spent(): void
    {
        Queue::fake();

        $user = $this->trialMember(0);
        $story = $this->story($user);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social'])
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_a_trial_member_cannot_retry_an_in_flight_generation(): void
    {
        Queue::fake();

        $user = $this->trialMember(0);
        $story = $this->story($user, 'generating');

        $this->actingAs($user)
            ->post(route('stories.retry', $story))
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_a_trial_member_can_retry_a_failed_generation(): void
    {
        Queue::fake();

        $user = $this->trialMember(0);
        $story = $this->story($user, 'failed');

        $this->actingAs($user)
            ->post(route('stories.retry', $story))
            ->assertOk();

        Queue::assertPushed(GenerateStory::class);
    }

    public function test_the_generated_library_locks_everything_past_the_unlocked_count(): void
    {
        $user = $this->trialMember();
        $story = $this->story($user, 'generating');
        $story->update(['episode_limit' => Story::TRIAL_EPISODE_COUNT]);

        $mock = Mockery::mock(StoryGeneratorService::class);
        $mock->shouldReceive('generate')
            ->once()
            ->andReturnUsing(function ($profile, int $count) {
                $episodes = [];
                for ($i = 1; $i <= $count; $i++) {
                    $episodes[] = ['episode_number' => $i, 'title' => "Episode $i", 'content' => "Content $i"];
                }

                return ['story_title' => 'Trial Story', '_tokens_input' => 0, '_tokens_output' => 0, 'episodes' => $episodes];
            });

        (new GenerateStory($story, 'social'))->handle($mock);

        $story = $story->fresh(['episodes', 'user']);

        $this->assertSame(Story::TRIAL_EPISODE_COUNT, $story->episodes->count());

        $locked = $story->episodes->filter(fn ($ep) => $ep->isLocked());

        $this->assertSame(Story::TRIAL_EPISODE_COUNT - Story::TRIAL_UNLOCKED_EPISODES, $locked->count());
    }

    /**
     * The story() fixture helper sets created_on_trial at build time, which
     * is not how a real interview creates a story — StoryController::init()
     * makes the row before generation, leaving created_on_trial at its
     * database default. This drives generate() the way the UI actually does,
     * against a story that starts without the flag, to catch generate()
     * silently leaving it unset (it did, once).
     */
    public function test_generating_through_the_real_endpoint_locks_the_library(): void
    {
        $user = $this->trialMember();
        $profile = BusinessProfile::factory()->for($user)->create();

        // Mirrors what StoryController::init() persists: no created_on_trial.
        $story = Story::factory()->for($user)->for($profile)->create([
            'status' => 'interview_complete',
        ]);
        $this->assertFalse($story->fresh()->created_on_trial);

        $mock = Mockery::mock(StoryGeneratorService::class);
        $mock->shouldReceive('generate')
            ->once()
            ->andReturnUsing(function ($profile, int $count) {
                $episodes = [];
                for ($i = 1; $i <= $count; $i++) {
                    $episodes[] = ['episode_number' => $i, 'title' => "Episode $i", 'content' => "Content $i"];
                }

                return ['story_title' => 'Trial Story', '_tokens_input' => 0, '_tokens_output' => 0, 'episodes' => $episodes];
            });
        $this->app->instance(StoryGeneratorService::class, $mock);

        // phpunit.xml sets QUEUE_CONNECTION=sync, so this dispatch — the real
        // one generate() makes, not a manually-run job — executes inline
        // against the mock bound above before the response comes back.
        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social'])
            ->assertRedirect(route('stories.show', $story));

        $story = $story->fresh(['episodes', 'user']);

        $this->assertTrue($story->created_on_trial);
        $this->assertTrue($story->locksEpisodes());

        $locked = $story->episodes->filter(fn ($ep) => $ep->isLocked());
        $this->assertSame(Story::TRIAL_EPISODE_COUNT - Story::TRIAL_UNLOCKED_EPISODES, $locked->count());

        // And the page the member actually lands on agrees.
        $this->actingAs($user->fresh())
            ->get(route('stories.show', $story))
            ->assertInertia(fn ($page) => $page
                ->where('locks_episodes', true)
                ->where('unlock_cost', Story::TRIAL_EPISODE_COUNT - Story::TRIAL_UNLOCKED_EPISODES)
                ->where('story.episodes.11.locked', true)
                ->missing('story.episodes.11.content')
                ->where('story.episodes.0.locked', false)
            );
    }

    public function test_the_trial_story_appears_in_the_members_own_story_list(): void
    {
        $user = $this->trialMember();
        $story = $this->story($user, 'draft');

        $this->actingAs($user)
            ->get(route('stories.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Stories/Index')
                ->has('stories', 1)
                ->where('stories.0.id', $story->id)
            );
    }

    // -------------------------------------------------------------------------
    // Paying members are unaffected
    // -------------------------------------------------------------------------

    public function test_a_paying_member_still_chooses_a_count_and_spends_credits(): void
    {
        Queue::fake();

        $user = User::factory()->create(['credits' => 20]);
        $story = $this->story($user);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social', 'episode_count' => 12])
            ->assertRedirect(route('stories.show', $story));

        $this->assertSame(8, $user->fresh()->credits);
        $this->assertSame(12, $story->fresh()->episode_limit);
    }

    public function test_a_paying_member_must_still_supply_an_episode_count(): void
    {
        Queue::fake();

        $user = User::factory()->create(['credits' => 20]);
        $story = $this->story($user);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social'])
            ->assertSessionHasErrors('episode_count');

        Queue::assertNothingPushed();
    }

    public function test_a_paying_member_is_still_held_to_their_tier(): void
    {
        Queue::fake();

        $user = User::factory()->create(['credits' => 100]);
        $story = $this->story($user);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social', 'episode_count' => 24])
            ->assertSessionHasErrors('episode_count');

        $this->assertSame(100, $user->fresh()->credits);
        Queue::assertNothingPushed();
    }

    public function test_the_store_route_charges_credits_for_a_paying_member(): void
    {
        Queue::fake();

        $user = User::factory()->create(['credits' => 20]);

        $this->actingAs($user)
            ->post(route('stories.store'), [
                'business_name' => 'Barnacle Busters',
                'messages' => [
                    ['role' => 'assistant', 'content' => 'How did you start?'],
                    ['role' => 'user', 'content' => 'Cleaning boats as a kid.'],
                ],
                'format' => 'social',
                'episode_count' => 12,
            ])
            ->assertRedirect();

        $this->assertSame(8, $user->fresh()->credits);
    }

    public function test_the_store_route_spends_trial_allowance_for_a_trial_member(): void
    {
        Queue::fake();

        $user = $this->trialMember();

        $this->actingAs($user)
            ->post(route('stories.store'), [
                'business_name' => 'Barnacle Busters',
                'messages' => [
                    ['role' => 'assistant', 'content' => 'How did you start?'],
                    ['role' => 'user', 'content' => 'Cleaning boats as a kid.'],
                ],
                'format' => 'social',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame(0, $user->trial_allowance);
        $this->assertSame(0, $user->credits);
        $this->assertSame(Story::TRIAL_EPISODE_COUNT, Story::where('user_id', $user->id)->first()->episode_limit);
    }
}
