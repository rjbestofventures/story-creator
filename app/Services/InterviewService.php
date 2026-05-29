<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class InterviewService
{
    private array $questions = [
        'How did you end up doing what you do today?',
        'Looking back, which experiences really prepared you to start your own business?',
        'What was the moment you knew you were ready to do this on your own?',
        'What is the most important thing you learned the hard way?',
        'How do your past experiences show up in the way you run your business now?',
        'What would you like your team and customers to say about working with you?',
        'Do you remember your worst customer service nightmare, and how you resolved it?',
        'What is one thing you absolutely refuse to compromise on in your business?',
        'Is there any part of running the business that still keeps you up at night?',
        'What keeps you motivated to do this work day after day?',
        'Has there been anyone who has always had your back along the way?',
        'Are there any causes, communities, or local efforts that matter to you or your business?',
        'How would you describe where your business is right now?',
        'What is something you are proud of but do not usually talk about?',
        'If people were talking about your business when you were not in the room, what would you hope they would say?',
        'Is there anything important about you or your business that we have not talked about yet?',
    ];

    private array $acknowledgments = [
        'That really comes through.',
        'That makes a lot of sense.',
        'Thank you for sharing that.',
        "It's clear that experience shaped you.",
        'That honesty is refreshing.',
        'What a meaningful moment.',
        'That story says a lot about you.',
        "I can see why that stuck with you.",
        'That perspective is really valuable.',
        'It sounds like that was a turning point.',
    ];

    public function getNextMessage(array $messages, array $profile): array
    {
        // Count real user answers — the hidden bootstrap message doesn't count
        $answerCount = 0;
        foreach ($messages as $msg) {
            if ($msg['role'] === 'user' && $msg['content'] !== 'Please begin the interview.') {
                $answerCount++;
            }
        }

        // All 16 questions answered — complete without an extra API call
        if ($answerCount >= count($this->questions)) {
            return ['message' => '', 'complete' => true];
        }

        $currentQuestion = $this->questions[$answerCount];

        // First question: no API call — hardcode the opening
        if ($answerCount === 0) {
            return [
                'message'  => "Great to meet you! Let's jump right in — {$currentQuestion}",
                'complete' => false,
            ];
        }

        $acknowledgment = $this->acknowledgments[array_rand($this->acknowledgments)];

        Log::debug('InterviewService payload', [
            'answer_count'     => $answerCount,
            'current_question' => $currentQuestion,
            'acknowledgment'   => $acknowledgment,
            'messages'         => $messages,
        ]);

        return [
            'message'  => "{$acknowledgment}\n\n{$currentQuestion}",
            'complete' => false,
        ];
    }
}
