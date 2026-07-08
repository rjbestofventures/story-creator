<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_url',
        'industry',
        'biography',
        'services',
        'linkedin_url',
        'social_url',
        'website_content',
        'answers',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /**
     * Number of real typed answers the user gave during the interview
     * (excludes synthetic "[Ready …]" button markers).
     */
    public function answeredCount(): int
    {
        return collect($this->answers ?? [])
            ->filter(fn ($m) => ($m['role'] ?? null) === 'user'
                && ! str_starts_with((string) ($m['content'] ?? ''), '['))
            ->count();
    }

    /**
     * Collapse the raw interview transcript into clean question/answer pairs
     * for review. Mirrors the frontend's question-numbering logic: a question
     * turn is an assistant message immediately followed by a typed answer, and
     * a re-asked question (invalid answer retry) overwrites the previous pair
     * rather than counting again.
     *
     * @return list<array{number:int,question:string,answer:?string}>
     */
    public function interviewQaPairs(): array
    {
        $messages = $this->answers ?? [];
        $pairs = [];
        $number = 0;
        $lastQuestion = null;

        foreach ($messages as $i => $msg) {
            if (($msg['role'] ?? null) !== 'assistant') {
                continue;
            }

            $next = $messages[$i + 1] ?? null;
            $nextIsAnswer = $next
                && ($next['role'] ?? null) === 'user'
                && ! str_starts_with((string) ($next['content'] ?? ''), '[');

            $content = (string) ($msg['content'] ?? '');
            $question = $msg['_question']
                ?? ($nextIsAnswer ? trim((string) collect(explode("\n\n", $content))->last()) : null);

            if (! $question) {
                continue;
            }

            $answer = $nextIsAnswer ? ($next['content'] ?? null) : null;

            $isRetry = ($msg['_retry'] ?? false)
                || ($lastQuestion !== null && $question === $lastQuestion);
            $lastQuestion = $question;

            if ($isRetry && ! empty($pairs)) {
                $pairs[count($pairs) - 1]['answer'] = $answer;

                continue;
            }

            $number++;
            $pairs[] = [
                'number' => $number,
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $pairs;
    }
}
