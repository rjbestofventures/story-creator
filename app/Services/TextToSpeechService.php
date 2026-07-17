<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TextToSpeechService
{
    /** OpenAI's audio.speech endpoint rejects input longer than this. */
    private const MAX_INPUT_LENGTH = 4096;

    public function synthesize(string $text, string $voice = 'nova'): string
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/audio/speech', [
                'model' => 'gpt-4o-mini-tts',
                'voice' => $voice,
                'input' => mb_substr($text, 0, self::MAX_INPUT_LENGTH),
                'instructions' => 'Speak in a warm, natural, conversational human tone — relaxed pacing with genuine inflection, not flat or robotic.',
            ]);

        if (! $response->successful()) {
            Log::error('OpenAI text-to-speech failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Text-to-speech failed.');
        }

        return $response->body();
    }
}
