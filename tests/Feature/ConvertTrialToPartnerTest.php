<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\CreditPack;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ConvertTrialToPartnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.provision_api_token' => 'test-token']);
    }

    private function convert(string $email): TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/provision/convert-to-partner', ['email' => $email]);
    }

    private function trialMember(): User
    {
        return User::factory()->create([
            'is_trial' => true,
            'trial_allowance' => 1,
            'credits' => 0,
            'is_verified_partner' => false,
        ]);
    }

    private function library(User $user): Story
    {
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
                'content' => "Secret content for episode $i",
                'format' => 'social',
                'status' => 'draft',
            ]);
        }

        return $story;
    }

    public function test_it_confers_partner_status_and_ends_the_trial(): void
    {
        $user = $this->trialMember();

        $this->convert($user->email)
            ->assertOk()
            ->assertJsonPath('user.is_verified_partner', true)
            ->assertJsonPath('user.is_trial', false)
            ->assertJsonPath('user.trial_allowance', 0);

        $user->refresh();

        $this->assertTrue($user->is_verified_partner);
        $this->assertFalse($user->is_trial);
        $this->assertSame(0, $user->trial_allowance);
    }

    public function test_the_library_stays_locked_after_conversion(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->convert($user->email)->assertOk();

        $story = $story->fresh(['episodes', 'user']);

        $this->assertTrue($story->locksEpisodes());
        $this->assertTrue($story->episodes->last()->isLocked());
        $this->assertFalse($story->episodes->first()->isLocked());
    }

    public function test_withheld_content_still_does_not_reach_the_page(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->convert($user->email)->assertOk();

        $this->actingAs($user->fresh())
            ->get(route('stories.show', $story))
            ->assertInertia(fn ($page) => $page
                ->where('story.episodes.11.locked', true)
                ->missing('story.episodes.11.content')
            );
    }

    public function test_it_grants_the_partner_conversion_credits(): void
    {
        $user = $this->trialMember();

        $this->convert($user->email)
            ->assertOk()
            ->assertJsonPath('user.credits', User::PARTNER_CONVERSION_CREDITS);

        $this->assertSame(User::PARTNER_CONVERSION_CREDITS, $user->fresh()->credits);
    }

    public function test_repeating_it_does_not_grant_the_credits_twice(): void
    {
        $user = $this->trialMember();

        $this->convert($user->email)->assertOk();
        $this->convert($user->email)->assertOk();

        $this->assertSame(User::PARTNER_CONVERSION_CREDITS, $user->fresh()->credits);
    }

    public function test_the_converted_member_is_offered_partner_pricing(): void
    {
        $user = $this->trialMember();

        $this->convert($user->email)->assertOk();

        $this->assertSame('partner', CreditPack::audienceType($user->fresh()));
    }

    public function test_it_is_safe_to_repeat(): void
    {
        $user = $this->trialMember();

        $this->convert($user->email)->assertOk();
        $this->convert($user->email)->assertOk()->assertJsonPath('user.is_verified_partner', true);

        $this->assertTrue($user->fresh()->is_verified_partner);
    }

    public function test_it_works_on_a_member_who_was_never_in_trial(): void
    {
        $user = User::factory()->create(['is_trial' => false, 'credits' => 20, 'is_verified_partner' => false]);

        $this->convert($user->email)->assertOk();

        $user->refresh();

        $this->assertTrue($user->is_verified_partner);
        $this->assertFalse($user->is_trial);
        $this->assertSame(20 + User::PARTNER_CONVERSION_CREDITS, $user->credits);
    }

    public function test_verify_partner_still_leaves_a_trial_running(): void
    {
        $user = $this->trialMember();
        $story = $this->library($user);

        $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/provision/verify-partner', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('user.is_trial', true);

        $user->refresh();

        $this->assertTrue($user->is_trial);
        $this->assertTrue($user->is_verified_partner);
        $this->assertTrue($story->fresh(['episodes', 'user'])->episodes->last()->isLocked());
    }

    public function test_it_requires_a_valid_token(): void
    {
        $user = $this->trialMember();

        $this->postJson('/api/provision/convert-to-partner', ['email' => $user->email])
            ->assertUnauthorized();

        $this->assertTrue($user->fresh()->is_trial);
    }

    public function test_an_unknown_email_is_not_found(): void
    {
        $this->convert('nobody@example.com')->assertNotFound();
    }
}
