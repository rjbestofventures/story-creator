<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ElevenLabsService
{
    /** ElevenLabs' convert endpoint has no hard input cap like OpenAI's, but keep a sane ceiling. */
    private const MAX_INPUT_LENGTH = 5000;

    /** "George" — a premade voice present on every account, used until an admin picks one. */
    public const DEFAULT_VOICE = 'JBFqnCBsd6RMkjVDRZzb';

    /** "Sarah" — distinct from the default so bot/customer narration doesn't sound identical. */
    public const DEFAULT_CUSTOMER_VOICE = 'EXAVITQu4vr4xnSDxMaL';

    /** The two options behind the Male/Female toggle on the "My Answers" page — "George" and "Sarah". */
    public const ANSWER_MALE_VOICE = 'JBFqnCBsd6RMkjVDRZzb';

    public const ANSWER_FEMALE_VOICE = 'EXAVITQu4vr4xnSDxMaL';

    public function synthesize(string $text, string $voiceId): string
    {
        $response = Http::withHeaders([
            'xi-api-key' => config('services.elevenlabs.key'),
            'Content-Type' => 'application/json',
        ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => mb_substr($text, 0, self::MAX_INPUT_LENGTH),
            'model_id' => 'eleven_multilingual_v2',
        ]);

        if (! $response->successful()) {
            Log::error('ElevenLabs text-to-speech failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Text-to-speech failed.');
        }

        return $response->body();
    }
}
