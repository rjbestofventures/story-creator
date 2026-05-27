<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\BusinessProfile;
use App\Models\Episode;
use App\Models\Story;

class StoryGeneratorService
{
    private string $systemPrompt = <<<'PROMPT'
You are a brand storytelling expert. Your job is to transform a business owner's answers into a series of compelling, authentic story episodes suitable for social media, LinkedIn, and blog use.

STRICT RULES:
1. FIRST-PERSON POV — Always write as the business owner ("I", "we", "my"). Never third-person.
2. ANTI-SALES — Never pitch, promote, or sell. No "book now", "click here", "limited offer". Zero sales language.
3. SOFT CTA — Each episode ends with a single, gentle, curiosity-driven question or reflection. Never a call to action.
4. AUTHENTIC VOICE — Conversational, warm, human. Not corporate. Not polished to the point of losing personality.
5. NARRATIVE ARC — Each episode should stand alone but feel like part of a bigger story.
6. LENGTH — Each episode: 150–200 words for social format, 300–400 words for blog format.

OUTPUT FORMAT (return valid JSON only, no markdown fences, no explanation):
{
  "story_title": "...",
  "episodes": [
    {
      "episode_number": 1,
      "title": "...",
      "content": "..."
    }
  ]
}
PROMPT;

    private function client(): Client
    {
        return new Client(
            apiKey:    config('anthropic.api_key')    ?: null,
            authToken: config('anthropic.auth_token') ?: null,
            baseUrl:   config('anthropic.base_url')   ?: null,
        );
    }

    public function generate(BusinessProfile $profile, int $episodeCount = 5, string $format = 'social'): array
    {
        $lengthGuide = $format === 'blog' ? '300–400 words' : '150–200 words';

        // Build transcript from stored conversation messages
        $transcript = '';
        foreach ($profile->answers as $msg) {
            $label = $msg['role'] === 'assistant' ? 'StoryBot' : 'Owner';
            $transcript .= "[{$label}]: {$msg['content']}\n\n";
        }

        $userPrompt = <<<PROMPT
Business Name: {$profile->business_name}
Website: {$profile->business_url}
Industry: {$profile->industry}

INTERVIEW TRANSCRIPT:
{$transcript}

Generate {$episodeCount} story episodes in the "{$format}" format ({$lengthGuide} each).
Draw from the full interview — use specific details, moments, and the owner's own words.
Create a natural arc: origin → work → values → impact → vision.
Return valid JSON only — no markdown, no explanation, just the JSON object.
PROMPT;

        $response = $this->client()->messages->create(
            maxTokens: 4096,
            messages: [
                ['role' => 'user', 'content' => $userPrompt],
            ],
            model: config('anthropic.model', env('ANTHROPIC_MODEL', 'claude-sonnet-4-6')),
            system: $this->systemPrompt,
            temperature: 0.8,
        );

        $content = $response->content[0]->text;

        // Strip any accidental markdown fences
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);

        return json_decode(trim($content), true);
    }

    public function saveToStory(BusinessProfile $profile, array $generated, string $format = 'social'): Story
    {
        $story = Story::create([
            'user_id'             => $profile->user_id,
            'business_profile_id' => $profile->id,
            'title'               => $generated['story_title'],
            'status'              => 'draft',
        ]);

        foreach ($generated['episodes'] as $ep) {
            Episode::create([
                'story_id'       => $story->id,
                'episode_number' => $ep['episode_number'],
                'title'          => $ep['title'],
                'content'        => $ep['content'],
                'format'         => $format,
                'status'         => 'draft',
            ]);
        }

        return $story->load('episodes');
    }
}
