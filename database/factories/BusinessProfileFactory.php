<?php

namespace Database\Factories;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessProfileFactory extends Factory
{
    protected $model = BusinessProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => fake()->company(),
            'answers' => [],
        ];
    }
}
