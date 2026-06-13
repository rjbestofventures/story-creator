<?php

namespace Database\Factories;

use App\Models\BusinessProfile;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoryFactory extends Factory
{
    protected $model = Story::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_profile_id' => BusinessProfile::factory(),
            'title' => null,
            'status' => 'interviewing',
            'is_demo' => false,
        ];
    }
}
