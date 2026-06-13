<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EffectiveEpisodeLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_plan_limit_when_subscription_has_no_override(): void
    {
        $plan = Plan::factory()->create(['episode_limit' => 12]);
        $sub = UserSubscription::factory()->for($plan)->create(['episode_limit' => 0]);

        $this->assertSame(12, $sub->effectiveEpisodeLimit());
    }

    public function test_returns_subscription_override_when_set(): void
    {
        $plan = Plan::factory()->create(['episode_limit' => 12]);
        $sub = UserSubscription::factory()->for($plan)->create(['episode_limit' => 20]);

        $this->assertSame(20, $sub->effectiveEpisodeLimit());
    }
}
