<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Story;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TemporaryVbpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.provision_api_token' => 'test-token']);
        Role::findOrCreate('user');
        Notification::fake();
        Queue::fake();
    }

    private function api(string $uri, array $body): TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer test-token')->postJson($uri, $body);
    }

    private function temporaryVbp(): User
    {
        $user = User::factory()->create(['credits' => 0]);
        $user->becomeTemporaryPartner();

        return $user->fresh();
    }

    private function interviewedStory(User $user): Story
    {
        $profile = BusinessProfile::factory()->for($user)->create();

        return Story::factory()->for($user)->for($profile)->create(['status' => 'interview_complete']);
    }

    // -------------------------------------------------------------------------
    // Creating one through the API
    // -------------------------------------------------------------------------

    public function test_the_endpoint_creates_a_temporary_vbp_with_12_credits_and_a_three_month_clock(): void
    {
        $this->freezeTime();

        $this->api('/api/provision/temporary-vbp', ['name' => 'Tess Temp', 'email' => 'tess@example.com'])
            ->assertCreated()
            ->assertJsonPath('user.is_temporary_vbp', true)
            ->assertJsonPath('user.is_verified_partner', false)
            ->assertJsonPath('user.is_trial', false)
            ->assertJsonPath('user.credits', 12)
            ->assertJsonPath('user.vbp_plan', null)
            ->assertJsonPath('user.temporary_vbp_expires_at', now()->addMonths(3)->toIso8601String());

        $user = User::where('email', 'tess@example.com')->first();

        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('user'));
        Notification::assertSentTo($user, AccountCreatedNotification::class);
    }

    public function test_the_endpoint_requires_the_token_and_a_unique_email(): void
    {
        $this->postJson('/api/provision/temporary-vbp', ['name' => 'X', 'email' => 'x@example.com'])
            ->assertUnauthorized();

        User::factory()->create(['email' => 'taken@example.com']);

        $this->api('/api/provision/temporary-vbp', ['name' => 'X', 'email' => 'taken@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    // -------------------------------------------------------------------------
    // Generating
    // -------------------------------------------------------------------------

    public function test_the_picker_offers_6_episodes_and_locks_12_18_and_24(): void
    {
        $this->actingAs($this->temporaryVbp())
            ->get(route('stories.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('is_temporary_vbp', true)
                ->where('max_episodes', 6)
                ->where('episode_options', [
                    ['count' => 6, 'locked' => false, 'unlock_label' => null],
                    ['count' => 12, 'locked' => true, 'unlock_label' => null],
                    ['count' => 18, 'locked' => true, 'unlock_label' => null],
                    ['count' => 24, 'locked' => true, 'unlock_label' => null],
                ])
            );
    }

    public function test_a_6_episode_story_spends_6_credits_and_nothing_is_locked(): void
    {
        $user = $this->temporaryVbp();
        $story = $this->interviewedStory($user);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['episode_count' => 6])
            ->assertRedirect(route('stories.show', $story));

        $story->refresh();
        $user->refresh();

        $this->assertSame(6, $story->episode_limit);
        $this->assertFalse($story->created_on_trial);
        $this->assertSame(6, $user->credits);
        $this->assertTrue($user->hasUsedTemporaryStory());
    }

    public function test_a_temporary_vbp_cannot_generate_12_episodes(): void
    {
        $user = $this->temporaryVbp();
        $story = $this->interviewedStory($user);

        $this->actingAs($user)
            ->post(route('stories.generate', $story), ['episode_count' => 12])
            ->assertSessionHasErrors('episode_count');

        $this->assertSame(12, $user->fresh()->credits);
    }

    public function test_only_one_story_even_with_credits_left_and_after_deleting_it(): void
    {
        $user = $this->temporaryVbp();
        $first = $this->interviewedStory($user);

        $this->actingAs($user)->post(route('stories.generate', $first), ['episode_count' => 6]);

        $first->delete();
        $second = $this->interviewedStory($user);

        $this->actingAs($user->fresh())
            ->post(route('stories.generate', $second), ['episode_count' => 6])
            ->assertForbidden();

        $this->assertSame(6, $user->fresh()->credits);

        $this->actingAs($user->fresh())
            ->get(route('stories.create'))
            ->assertRedirect(route('stories.index'));
    }

    public function test_the_shop_is_closed_to_a_temporary_vbp(): void
    {
        $this->actingAs($this->temporaryVbp())
            ->get(route('shop.index'))
            ->assertRedirect(route('stories.index'));
    }

    // -------------------------------------------------------------------------
    // Expiry
    // -------------------------------------------------------------------------

    public function test_the_account_is_deactivated_once_three_months_pass(): void
    {
        $user = $this->temporaryVbp();
        $partner = User::factory()->create(['is_verified_partner' => true]);

        $this->artisan('vbp:expire-temporary');
        $this->assertTrue($user->fresh()->is_active);

        $this->travel(3)->months();
        $this->travel(1)->minutes();

        $this->artisan('vbp:expire-temporary');

        $this->assertFalse($user->fresh()->is_active);
        $this->assertTrue($partner->fresh()->is_active);
    }

    // -------------------------------------------------------------------------
    // Converting to a full VBP
    // -------------------------------------------------------------------------

    public function test_convert_to_partner_turns_a_temporary_vbp_into_a_gold_vbp_keeping_leftover_credits(): void
    {
        $user = $this->temporaryVbp();
        $user->update(['credits' => 6]);

        $this->api('/api/provision/convert-to-partner', ['email' => $user->email, 'vbp_plan' => 'gold'])
            ->assertOk()
            ->assertJsonPath('user.is_verified_partner', true)
            ->assertJsonPath('user.is_temporary_vbp', false)
            ->assertJsonPath('user.temporary_vbp_expires_at', null)
            ->assertJsonPath('user.vbp_plan', 'gold')
            ->assertJsonPath('user.credits', 6 + 48);

        $user->refresh();

        $this->assertSame(12, $user->maxEpisodes());
        $this->assertFalse($user->hasUsedTemporaryStory());
    }

    public function test_converting_an_expired_temporary_vbp_reactivates_the_account(): void
    {
        $user = $this->temporaryVbp();

        $this->travel(4)->months();
        $this->artisan('vbp:expire-temporary');
        $this->assertFalse($user->fresh()->is_active);

        $this->api('/api/provision/convert-to-partner', ['email' => $user->email, 'vbp_plan' => 'silver'])
            ->assertOk()
            ->assertJsonPath('user.is_active', true)
            ->assertJsonPath('user.credits', 12 + 36);

        $this->artisan('vbp:expire-temporary');
        $this->assertTrue($user->fresh()->is_active);
    }

    // -------------------------------------------------------------------------
    // VBP plan on the other endpoints
    // -------------------------------------------------------------------------

    public function test_create_user_with_a_plan_makes_a_partner_with_the_plan_credits(): void
    {
        $this->api('/api/provision/user', ['name' => 'Gia Gold', 'email' => 'gia@example.com', 'vbp_plan' => 'gold'])
            ->assertCreated()
            ->assertJsonPath('user.is_verified_partner', true)
            ->assertJsonPath('user.vbp_plan', 'gold')
            ->assertJsonPath('user.credits', 48);

        $this->api('/api/provision/user', ['name' => 'Sal Silver', 'email' => 'sal@example.com', 'vbp_plan' => 'silver'])
            ->assertCreated()
            ->assertJsonPath('user.credits', 36);
    }

    public function test_create_user_rejects_a_plan_with_a_trial_or_a_pack(): void
    {
        $this->api('/api/provision/user', ['name' => 'X', 'email' => 'x@example.com', 'vbp_plan' => 'gold', 'trial' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vbp_plan');

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_verify_partner_records_a_plan_without_granting_credits(): void
    {
        $user = User::factory()->create(['credits' => 5]);

        $this->api('/api/provision/verify-partner', ['email' => $user->email, 'vbp_plan' => 'silver'])
            ->assertOk()
            ->assertJsonPath('user.vbp_plan', 'silver')
            ->assertJsonPath('user.credits', 5);
    }

    public function test_deactivate_returns_the_account_summary(): void
    {
        $user = $this->temporaryVbp();

        $this->api('/api/provision/deactivate', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('is_active', false)
            ->assertJsonPath('user.is_temporary_vbp', true)
            ->assertJsonPath('user.vbp_plan', null);
    }

    public function test_sanctum_users_endpoint_accepts_a_plan(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/users', ['name' => 'Pat', 'email' => 'pat@example.com', 'vbp_plan' => 'gold'])
            ->assertCreated()
            ->assertJsonPath('vbp_plan', 'gold')
            ->assertJsonPath('is_verified_partner', true)
            ->assertJsonPath('credits', 48);
    }
}
