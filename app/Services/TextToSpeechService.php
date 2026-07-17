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
                'model' => 'tts-1',
                'voice' => $voice,
                'input' => mb_substr($text, 0, self::MAX_INPUT_LENGTH),
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
