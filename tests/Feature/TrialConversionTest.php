<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\CreditPack;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrialConversionTest extends TestCase
{
    use RefreshDatabase;

    private function pack(string $type, string $slug, int $credits = 48): CreditPack
    {
        return CreditPack::create([
            'slug' => $slug,
            'label' => ucfirst($type).' Pack',
            'type' => $type,
            'credits' => $credits,
            'max_episodes' => 12,
            'price' => 18000,
            'is_active' => true,
            'stripe_price_id' => 'price_'.$slug,
        ]);
    }

    private function trialMember(): User
    {
        return User::factory()->create(['is_trial' => true, 'trial_allowance' => 1, 'credits' => 0]);
    }

    private function library(User $user): Story
    {
        $profile = BusinessProfile::factory()->for($user)->create();
        $story = Story::factory()->for($user)->for($profile)->create([
            'status' => 'draft',
            'episode_limit' => Story::TRIAL_EPISODE_COUNT,
        ]);

        for ($i = 1; $i <= Story::TRIAL_EPISODE_COUNT; $i++) {
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

    // -------------------------------------------------------------------------
    // Ending the trial
    // -------------------------------------------------------------------------

    public function test_acquiring_a_main_pack_ends_the_trial_and_keeps_the_credits(): void
    {
        $user = $this->trialMember();

        $this->pack('storybot', 'storybot-basic')->grantTo($user);

        $user->refresh();

        $this->assertFalse($user->is_trial);
        $this->assertSame(0, $user->trial_allowance);
        $this->assertSame(48, $user->credits);
    }

    public function test_the_whole_library_unlocks_with_no_regeneration(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $titlesBefore = $story->episodes->pluck('title', 'episode_number')->all();
        $contentBefore = $story->episodes->pluck('content', 'episode_number')->all();

        $this->pack('storybot', 'storybot-basic')->grantTo($user);

        $story = $story->fresh(['episodes', 'user']);

        foreach ($story->episodes as $episode) {
            $this->assertFalse($episode->isLocked());
            $this->assertSame($titlesBefore[$episode->episode_number], $episode->title);
            $this->assertSame($contentBefore[$episode->episode_number], $episode->content);
        }

        $this->assertSame(Story::TRIAL_EPISODE_COUNT, $story->episodes->count());
    }

    public function test_unlocked_content_reaches_the_page_after_conversion(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->pack('storybot', 'storybot-basic')->grantTo($user);

        $this->actingAs($user->fresh())
            ->get(route('stories.show', $story))
            ->assertInertia(fn ($page) => $page
                ->where('story.episodes.11.content', 'Secret content for episode 12')
                ->where('story.episodes.11.locked', false)
            );
    }

    // -------------------------------------------------------------------------
    // Partner status
    // -------------------------------------------------------------------------

    public function test_acquiring_a_partner_pack_confers_partner_status(): void
    {
        $user = User::factory()->create(['is_verified_partner' => false]);

        $this->pack('partner', 'partner-basic')->grantTo($user);

        $this->assertTrue($user->fresh()->is_verified_partner);
    }

    public function test_acquiring_a_retail_pack_does_not_confer_partner_status(): void
    {
        $user = User::factory()->create(['is_verified_partner' => false]);

        $this->pack('storybot', 'storybot-basic')->grantTo($user);

        $this->assertFalse($user->fresh()->is_verified_partner);
    }

    public function test_an_admin_granted_partner_pack_confers_partner_status(): void
    {
        $user = User::factory()->create(['is_verified_partner' => false, 'is_trial' => true, 'trial_allowance' => 1]);
        $pack = $this->pack('partner', 'partner-basic');

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($admin)
            ->post(route('admin.users.assign-plan', $user), ['pack_id' => $pack->id])
            ->assertRedirect();

        $user->refresh();

        $this->assertTrue($user->is_verified_partner);
        $this->assertFalse($user->is_trial);
    }

    // -------------------------------------------------------------------------
    // Add-ons
    // -------------------------------------------------------------------------

    public function test_an_addon_ends_no_trial_and_confers_no_partner_status(): void
    {
        $user = User::factory()->create(['is_trial' => true, 'trial_allowance' => 1, 'credits' => 0]);

        $this->pack('addon', 'credit-boost', 12)->grantTo($user);

        $user->refresh();

        $this->assertTrue($user->is_trial);
        $this->assertFalse($user->is_verified_partner);
        $this->assertSame(12, $user->credits);
    }

    public function test_a_trial_member_is_not_offered_the_addon(): void
    {
        $user = $this->trialMember();
        $this->pack('storybot', 'storybot-basic');
        $this->pack('addon', 'credit-boost', 12);

        $this->actingAs($user)
            ->get(route('shop.index'))
            ->assertInertia(fn ($page) => $page->where('canBuyAddon', false));
    }

    public function test_a_vetted_trial_member_is_not_offered_the_addon(): void
    {
        $user = User::factory()->create([
            'is_trial' => true,
            'trial_allowance' => 1,
            'credits' => 0,
            'is_verified_partner' => true,
        ]);
        $this->pack('partner', 'partner-basic');
        $this->pack('addon', 'credit-boost', 12);

        $this->actingAs($user)
            ->get(route('shop.index'))
            ->assertInertia(fn ($page) => $page->where('canBuyAddon', false));
    }

    public function test_a_trial_member_cannot_check_out_the_addon(): void
    {
        $user = User::factory()->create([
            'is_trial' => true,
            'trial_allowance' => 1,
            'credits' => 0,
            'is_verified_partner' => true,
        ]);
        $addon = $this->pack('addon', 'credit-boost', 12);

        $this->actingAs($user)
            ->post(route('shop.checkout'), ['pack_id' => $addon->id])
            ->assertStatus(422);
    }

    public function test_a_verified_partner_who_is_not_in_trial_can_still_buy_the_addon(): void
    {
        $user = User::factory()->create(['is_verified_partner' => true, 'credits' => 5]);
        $this->pack('addon', 'credit-boost', 12);

        $this->actingAs($user)
            ->get(route('shop.index'))
            ->assertInertia(fn ($page) => $page->where('canBuyAddon', true));
    }

    public function test_the_addon_becomes_available_once_the_trial_ends(): void
    {
        $user = $this->trialMember();
        $this->pack('addon', 'credit-boost', 12);

        $this->pack('storybot', 'storybot-basic')->grantTo($user);

        $this->actingAs($user->fresh())
            ->get(route('shop.index'))
            ->assertInertia(fn ($page) => $page->where('canBuyAddon', true));
    }
}
