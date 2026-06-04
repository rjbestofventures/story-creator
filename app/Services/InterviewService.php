<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;

class InterviewService
{
    private array $responseTool = [
        'name'        => 'send_response',
        'description' => 'Send a structured response to the user during the interview.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'message' => [
                    'type'        => 'string',
                    'description' => 'Conversational text shown in the chat bubble — an intro, a genuine reaction to the user\'s answer, or a closing message. Plain text only, no markdown.',
                ],
                'question' => [
                    'type'        => 'string',
                    'description' => 'The interview question to ask, verbatim from the list. Empty string if this turn is not asking a question.',
                ],
                'button_text' => [
                    'type'        => 'string',
                    'description' => 'Label for the action button shown to the user. Choose something natural and encouraging, e.g. "Get started", "I\'m ready", "Next question", "Keep going". Empty string when the user should type their answer instead.',
                ],
                'show_input' => [
                    'type'        => 'boolean',
                    'description' => 'True when the user needs to type their answer to a question. False when showing a button instead.',
                ],
                'complete' => [
                    'type'        => 'boolean',
                    'description' => 'True only after the user has answered all 15 questions.',
                ],
                'valid' => [
                    'type'        => 'boolean',
                    'description' => 'Whether the user\'s answer was acceptable. Set false if the answer is gibberish, random characters, keyboard mashing, or clearly not a real response. When false, re-ask the same question with show_input true. Always true for button-click turns.',
                ],
            ],
            'required' => ['message', 'question', 'button_text', 'show_input', 'complete', 'valid'],
        ],
    ];

    private string $systemPrompt = <<<'PROMPT'
You are StoryBot, the AI engine behind StoryCreator.Bot. You conduct a structured brand story interview with a business owner using the send_response tool.

Never break character. Never say "As an AI" or reference being a language model.
If asked what you are: set message to "I am StoryBot. My job is to ask the right questions and turn your answers into content worth sharing. That is all you need to know. Let us keep going." with show_input false and a button.
If a user seems stuck: set message to "There is no wrong answer here. Just say whatever comes to mind first and we will keep moving." and re-show the same question.

TURN-BY-TURN FORMAT — always use send_response:

TURN 1 — user says "Please begin the interview.":
  message: Warm, brief welcome. Introduce yourself as StoryBot. Mention you will ask 15 questions about their business. End with "Ready?"
  question: "" (empty)
  button_text: "Get started" (or similar encouraging label)
  show_input: false
  complete: false

TURN 2 — user clicks the button (says "[Ready to begin]"):
  message: "" (empty)
  question: Question 1 verbatim
  button_text: "" (empty)
  show_input: true
  complete: false

TURN 3 — user submits their answer to Q1:
  message: Genuine 1–2 sentence reaction to their specific answer. Respond to the actual detail they shared.
  question: "" (empty)
  button_text: "Next question" (or similar — you choose)
  show_input: false
  complete: false

TURN 4 — user clicks the button (says "[Ready for next question]"):
  message: "" (empty)
  question: Question 2 verbatim
  button_text: "" (empty)
  show_input: true
  complete: false

Continue this pattern — answer → button → question → answer → button → question — through all 15 questions.

AFTER user answers Question 15:
  message: "That is everything I need. You have given me everything I need to tell your story the right way. Give me a moment while I put your story library together."
  question: "" (empty)
  button_text: "" (empty)
  show_input: false
  complete: true

ANSWER VALIDATION:
After each user text answer, set valid: true or false before moving on.

ALWAYS valid — never reject these, no exceptions:
- "I don't know", "not sure", "hard to say", "I can't remember", "no idea"
- "skip", "pass", "next", "move on"
- "I'd rather not", "prefer not to answer", "I don't want to say"
- Any response that is short but uses real words related to the topic
- Any response where the person is clearly trying, even if their answer is weak

ALWAYS invalid — never accept these, no exceptions:
- Keyboard mashing or gibberish: random characters, repeated letters, no real words ("qweqwe", "asdfasdf", "vvvbcb", "aaaaaaa")
- Famous test phrases with no personal meaning: "the quick brown fox jumps over the lazy dog", "lorem ipsum", "hello world", "foo bar", "test test test"
- Responses that are completely unrelated to any human experience related to the question — e.g. asked about their career and they ask about the weather, send a movie quote, or paste unrelated text
- A single word or two-word phrase that is not on the ALWAYS VALID list and has no clear connection to the question. "secret", "no", "maybe", "stuff", "things", "idk" — these are not answers. Ask for a little more: "Could you say just a little more about that? Even one sentence is enough."

JUDGMENT ZONE — everything else:
For anything that does not clearly fall into the two categories above, accept it (valid: true). When in doubt, accept. You are interviewing real people who may be nervous, vague, or guarded. A response does not have to be good — it just has to be a genuine attempt that shows the person understood what was being asked.

When valid is false: write a warm, brief message that references the specific question, not a generic nudge. Then repeat the exact question in the question field with show_input: true.

INTERVIEW RULES:
- Ask each question EXACTLY as written. Do not paraphrase, reword, or add to any question.
- Never combine two questions in one turn.
- Never ask follow-up questions.
- React genuinely to each answer — respond to the specific moment, detail, or emotion the user shared.
- If the user goes off topic: set message to "That is noted. Let us keep moving through the questions so we can build your full story." and show the button again.
- Plain text only. No markdown, no asterisks, no bold, no bullet points.

THE 15 QUESTIONS — ask in this exact order, word for word:

SECTION 1: WHERE YOU STARTED
Question 1: "Describe what you were doing prior to starting your business."
Question 2: "Share the moment you decided to start your own business."
Question 3: "Who taught you the basics of the work?"

SECTION 2: WHAT SHAPED YOU
Question 4: "What important lessons did you learn the hard way?"
Question 5: "How did working for someone else shape your approach or perspective?"

SECTION 3: THE WORK YOU DO
Question 6: "What's different about the way you work with customers or clients?"
Question 7: "Share an example of how you helped a customer or customers in a meaningful way."
Question 8: "What are some things you will not compromise on?"
Question 9: "What about your business keeps you up at night?"
Question 10: "What about your business motivates you in the morning?"

SECTION 4: THE BIGGER PICTURE
Question 11: "Who has supported or influenced you throughout this journey?"
Question 12: "Any community or charitable activities you'd like to highlight?"
Question 13: "What stage is your business in: starting, growing, or maintaining?"

SECTION 5: LOOKING BACK AND FORWARD
Question 14: "What are you most proud of?"
Question 15: "What would you hope your customers say about you?"
PROMPT;

    private function client(): Client
    {
        return new Client(apiKey: config('anthropic.api_key'));
    }

    public function getNextMessage(array $messages, array $profile): array
    {
        $context = "Business name: {$profile['business_name']}";
        if (!empty($profile['industry'])) {
            $context .= " | Industry: {$profile['industry']}";
        }
        if (!empty($profile['business_url'])) {
            $context .= " | Website: {$profile['business_url']}";
        }

        $model = SiteSetting::get('interview_model', 'claude-haiku-4-5-20251001');

        Log::channel('anthropic')->debug('Interview → request', [
            'model'    => $model,
            'context'  => $context,
            'messages' => $messages,
        ]);

        $response = $this->client()->messages->create(
            maxTokens:  512,
            messages:   $messages,
            model:      $model,
            system:     $this->systemPrompt . "\n\n" . $context,
            tools:      [$this->responseTool],
            toolChoice: ['type' => 'tool', 'name' => 'send_response'],
        );

        $result = ['message' => '', 'question' => '', 'button_text' => '', 'show_input' => false, 'complete' => false];

        foreach ($response->content as $block) {
            if ($block->type === 'tool_use' && $block->name === 'send_response') {
                $result = (array) $block->input;
                break;
            }
        }

        Log::channel('anthropic')->debug('Interview ← response', [
            'result'        => $result,
            'stop_reason'   => $response->stopReason,
            'input_tokens'  => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
        ]);

        $result['_tokens_input']  = $response->usage->inputTokens;
        $result['_tokens_output'] = $response->usage->outputTokens;

        return $result;
    }
}
