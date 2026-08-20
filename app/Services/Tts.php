<?php

namespace App\Services;

use App\Models\SiteSetting;

/**
 * Routes every text-to-speech call through whichever provider is configured
 * in Settings > Voice, so call sites don't each need their own provider
 * branching. "Role" selects which set of voice settings applies — the main
 * voice (interview chat), the episode-playback voice, and the demo's bot/customer
 * voices are each configurable so the different surfaces can sound distinct. Each
 * falls back to the main voice when left unset. The "answer_male"/"answer_female"
 * roles are fixed rather than configurable — they back the Male/Female toggle the
 * reader picks on the "My Answers" page.
 */
class Tts
{
    public static function provider(): string
    {
        return SiteSetting::get('tts_provider', 'openai');
    }

    public static function voiceFor(string $role = 'main'): string
    {
        if (self::provider() === 'elevenlabs') {
            return match ($role) {
                'demo_bot' => SiteSetting::get('elevenlabs_demo_bot_voice') ?: SiteSetting::get('elevenlabs_voice', ElevenLabsService::DEFAULT_VOICE),
                'demo_customer' => SiteSetting::get('elevenlabs_demo_customer_voice', ElevenLabsService::DEFAULT_CUSTOMER_VOICE),
                'episode' => SiteSetting::get('elevenlabs_episode_voice') ?: SiteSetting::get('elevenlabs_voice', ElevenLabsService::DEFAULT_VOICE),
                'answer_male' => ElevenLabsService::ANSWER_MALE_VOICE,
                'answer_female' => ElevenLabsService::ANSWER_FEMALE_VOICE,
                default => SiteSetting::get('elevenlabs_voice', ElevenLabsService::DEFAULT_VOICE),
            };
        }

        return match ($role) {
            'demo_bot' => SiteSetting::get('demo_bot_voice') ?: SiteSetting::get('tts_voice', TextToSpeechService::DEFAULT_VOICE),
            'demo_customer' => SiteSetting::get('demo_customer_voice', TextToSpeechService::DEFAULT_CUSTOMER_VOICE),
            'episode' => SiteSetting::get('tts_episode_voice') ?: SiteSetting::get('tts_voice', TextToSpeechService::DEFAULT_VOICE),
            'answer_male' => TextToSpeechService::ANSWER_MALE_VOICE,
            'answer_female' => TextToSpeechService::ANSWER_FEMALE_VOICE,
            default => SiteSetting::get('tts_voice', TextToSpeechService::DEFAULT_VOICE),
        };
    }

    public static function speak(string $text, string $role = 'main'): string
    {
        $voice = self::voiceFor($role);

        if (self::provider() === 'elevenlabs') {
            return (new ElevenLabsService)->synthesize($text, $voice);
        }

        $instructions = SiteSetting::get('tts_instructions', TextToSpeechService::DEFAULT_INSTRUCTIONS);

        return (new TextToSpeechService)->synthesize($text, $voice, $instructions);
    }
}
