<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TranscriptionService
{
    public function transcribe(UploadedFile $file): string
    {
        $response = Http::withToken(config('services.openai.key'))
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
            ]);

        if (! $response->successful()) {
            Log::error('Whisper transcription failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Transcription failed.');
        }

        return trim($response->json('text', ''));
    }
}
