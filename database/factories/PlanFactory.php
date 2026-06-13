<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'episode_limit' => 5,
            'stories_per_month' => 2,
            'refine_monthly' => 10,
            'price_monthly' => 0,
            'price_yearly' => 0,
            'is_active' => true,
        ];
    }
}
