<?php

namespace App\Jobs;

use App\Models\SiteSetting;
use App\Models\Story;
use App\Notifications\StoryGeneratedNotification;
use App\Services\StoryGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateStory implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public Story $story,
        public string $format = 'social',
    ) {}

    public function handle(StoryGeneratorService $generator): void
    {
        $story = $this->story->load(['businessProfile', 'user']);
        $profile = $story->businessProfile;
        $user = $story->user;
        $count = $story->is_demo ? 3 : ($story->episode_limit ?? 12);

        try {
            $generated = $generator->generate($profile, $count, $this->format);

            $story->update([
                'title' => $generated['story_title'],
                'status' => 'draft',
                'generation_model' => SiteSetting::get('generation_model', 'claude-sonnet-4-6'),
                'tokens_input' => $story->tokens_input + ($generated['_tokens_input'] ?? 0),
                'tokens_output' => $story->tokens_output + ($generated['_tokens_output'] ?? 0),
            ]);

            foreach ($generated['episodes'] as $ep) {
                $story->episodes()->create([
                    'episode_number' => $ep['episode_number'],
                    'title' => $ep['title'],
                    'content' => $ep['content'],
                    'format' => $this->format,
                    'status' => 'draft',
                ]);
            }

            StoryGeneratedNotification::sendFor($story);
        } catch (\Throwable $e) {
            $story->update(['status' => 'failed']);
            throw $e;
        }
    }
}
