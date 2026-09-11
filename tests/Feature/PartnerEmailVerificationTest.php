<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_partner_can_reach_story_creation(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_verified_partner' => true,
            'credits' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('stories.create'))
            ->assertOk();
    }

    public function test_unverified_partner_can_reach_shop(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_verified_partner' => true,
        ]);

        $this->actingAs($user)
            ->get(route('shop.index'))
            ->assertOk();
    }

    public function test_unverified_partner_is_bounced_off_the_verification_prompt(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_verified_partner' => true,
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_unverified_non_partner_is_still_sent_to_verification_notice(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_verified_partner' => false,
            'credits' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('stories.create'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_non_partner_still_sees_the_verification_prompt(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_verified_partner' => false,
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk();
    }
}
