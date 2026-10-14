<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoryInitTest extends TestCase
{
    use RefreshDatabase;

    private function basics(array $overrides = []): array
    {
        return $overrides + [
            'business_name' => 'Jiji Studio',
            'business_url' => 'jiji.example.com',
            'industry' => 'Interior Design',
        ];
    }

    public function test_long_biography_and_services_start_the_interview(): void
    {
        Http::fake();
        $user = User::factory()->create(['credits' => 36]);

        $this->actingAs($user)
            ->postJson(route('stories.init'), $this->basics([
                'biography' => str_repeat('a', 3000),
                'services' => str_repeat('b', 3000),
                'industry' => str_repeat('c', 120),
            ]))
            ->assertOk()
            ->assertJsonStructure(['story_id']);
    }

    public function test_oversized_fields_return_a_json_validation_error(): void
    {
        Http::fake();
        $user = User::factory()->create(['credits' => 36]);

        $this->actingAs($user)
            ->postJson(route('stories.init'), $this->basics(['biography' => str_repeat('a', 5001)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('biography');
    }
}
