<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSubscriptionFactory extends Factory
{
    protected $model = UserSubscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'status' => 'active',
            'billing_interval' => 'monthly',
            'story_credits' => 5,
            'refine_credits' => 10,
            'episode_limit' => 0,
            'starts_at' => now()->subDay(),
            'expires_at' => null,
        ];
    }
}
