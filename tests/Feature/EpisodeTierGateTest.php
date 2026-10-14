<?php

namespace Tests\Feature;

use App\Jobs\GenerateStory;
use App\Models\BusinessProfile;
use App\Models\CreditPack;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EpisodeTierGateTest extends TestCase
{
    use RefreshDatabase;

    private function pack(string $type, int $maxEpisodes, string $slug): CreditPack
    {
        return CreditPack::create([
            'slug' => $slug,
            'label' => ucfirst($type)." {$maxEpisodes} Pack",
            'type' => $type,
            'credits' => 100,
            'max_episodes' => $maxEpisodes,
            'price' => 1000,
            'is_active' => true,
        ]);
    }

    public function test_max_episodes_defaults_to_12_without_packs(): void
    {
        $user = User::factory()->create();

        $this->assertSame(12, $user->maxEpisodes());
    }

    public function test_max_episodes_reflects_highest_main_pack(): void
    {
        $user = User::factory()->create();
        $this->pack('storybot', 18, 'sb-premium')->grantTo($user);
        $this->pack('storybot', 12, 'sb-basic')->grantTo($user);

        $this->assertSame(18, $user->fresh()->maxEpisodes());
    }

    public function test_addon_pack_does_not_unlock_tier(): void
    {
        $user = User::factory()->create();
        $this->pack('addon', 24, 'credit-boost')->grantTo($user);

        $this->assertSame(12, $user->fresh()->maxEpisodes());
    }

    public function test_vbp_silver_partner_reaches_18_episodes(): void
    {
        $user = User::factory()->create(['is_verified_partner' => true, 'vbp_plan' => 'silver']);

        $this->assertSame(18, $user->maxEpisodes());
    }

    public function test_vbp_gold_and_other_partners_reach_24_episodes(): void
    {
        $gold = User::factory()->create(['is_verified_partner' => true, 'vbp_plan' => 'gold']);
        $other = User::factory()->create(['is_verified_partner' => true, 'vbp_plan' => 'other']);

        $this->assertSame(24, $gold->maxEpisodes());
        $this->assertSame(24, $other->maxEpisodes());
    }

    public function test_complimentary_trial_does_not_get_a_vbp_ceiling(): void
    {
        $user = User::factory()->create(['is_verified_partner' => true, 'vbp_plan' => 'gold', 'is_temporary_vbp' => true]);

        $this->assertSame(12, $user->maxEpisodes());
    }

    public function test_silver_partner_sees_24_locked_behind_gold_plan(): void
    {
        $user = User::factory()->create(['is_verified_partner' => true, 'vbp_plan' => 'silver', 'credits' => 24]);
        $user->assignRole(Role::findOrCreate('user'));

        $this->actingAs($user)->get(route('stories.create'))
            ->assertInertia(fn ($page) => $page
                ->where('episode_options.0.locked', false)
                ->where('episode_options.1.locked', false)
                ->where('episode_options.2.locked', true)
                ->where('episode_options.2.unlock_label', 'Gold Plan'));
    }

    public function test_admin_has_unlimited_max_episodes(): void
    {
        Role::findOrCreate('admin');
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertNull($user->maxEpisodes());
    }

    public function test_generate_rejects_over_tier_episode_count(): void
    {
        Queue::fake();
        $user = User::factory()->create(['credits' => 100]);
        // No packs → tier ceiling is 12.
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create(['status' => 'interview_complete']);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social', 'episode_count' => 24])
            ->assertSessionHasErrors('episode_count');

        Queue::assertNothingPushed();
        $this->assertSame(100, $user->fresh()->credits);
    }

    public function test_generate_allows_at_tier_episode_count(): void
    {
        Queue::fake();
        $user = User::factory()->create(['credits' => 100]);
        $this->pack('storybot', 24, 'sb-professional')->grantTo($user);
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create(['status' => 'interview_complete']);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['format' => 'social', 'episode_count' => 24])
            ->assertRedirect(route('stories.show', $story));

        Queue::assertPushed(GenerateStory::class);
        $this->assertSame(24, $story->fresh()->episode_limit);
    }
}
