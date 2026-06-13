<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        return [
            'story_id'       => Story::factory(),
            'episode_number' => 1,
            'title'          => fake()->sentence(5),
            'content'        => fake()->paragraph(),
            'format'         => 'social',
            'status'         => 'draft',
        ];
    }
}
