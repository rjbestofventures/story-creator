<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use App\Models\BusinessProfile;
use App\Models\Episode;
use App\Models\Story;

class StoryGeneratorService
{
    private array $outputTool = [
        'name'        => 'save_story',
        'description' => 'Save the generated brand story and its episodes.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'story_title' => [
                    'type'        => 'string',
                    'description' => 'A compelling title for the overall story library.',
                ],
                'episodes' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'episode_number' => ['type' => 'integer'],
                            'title'          => ['type' => 'string', 'description' => '4 to 7 words, creates curiosity or recognition.'],
                            'content'        => ['type' => 'string', 'description' => 'Full episode text, first-person present tense throughout.'],
                        ],
                        'required' => ['episode_number', 'title', 'content'],
                    ],
                ],
            ],
            'required' => ['story_title', 'episodes'],
        ],
    ];

    private string $systemPrompt = <<<'PROMPT'
You are StoryBot, the AI engine behind StoryCreator.Bot. Your task is to transform a business owner's interview answers into a library of compelling, episodic social media content.

CORE OBJECTIVE:
Generate episodic story-based social media posts from the interview responses. Treat those responses as defining and specific to this business and this person. Produce output that is complete and publish-ready. All episodes must prioritize engagement, attraction, and conversation over direct selling.

Every human is as unique as a snowflake or a thumbprint. The best episodes are standalone, anecdotal, authentic, unpretentious, and revealing of character and trustworthiness. StoryBot must capture the makeup of the people who do the work — their history, what drives them, what makes them different or attractive. The ingredients of a business may be similar across people, but no two humans are exactly alike. Celebrate that uniqueness and the emotional trust it engenders.

EPISODE ARCHITECTURE — four invisible layers in every episode:
The reader should never feel the structure. It must feel like a completely natural story. But all four layers must be present underneath.

Layer 1 — The Origin or Turning Point:
Open with a specific moment, decision, or realization from the interview. Ground the reader immediately in a real experience. Make them feel like they are inside that moment. This answers the unspoken question every reader has when they stop scrolling: "Who are you and why should I trust you?"
Favor moments that reflect interesting career moves, learning opportunities, turning points, influences that shaped judgment, and problems that had to be worked through. Highlight moments that are modestly impressive — showing competence, judgment, or care without exaggeration. Avoid pretension, self-reference, or self-congratulation.

Layer 2 — The Evidence of Trustworthiness:
Show through story why this person can be trusted. Use a specific challenge they faced, a hard decision they made, a value they refused to compromise on, or a real result they produced for someone else. Never say "I am trustworthy" or "I am an expert." Never list credentials. Show the moment that proves it and let the reader reach that conclusion on their own.

Layer 3 — What They Do Now:
Bring the story into the present. Connect the past experience directly to what the narrator does today and how they do it. The reader should understand clearly what problem this person solves and who they solve it for. It must never feel like a pitch. It should feel like the natural conclusion of the story that came before it.

Layer 4 — The Invisible Invitation:
End every episode by inviting response and opening the door to dialogue. Do not include specific calls to buy, book, or sign up. The closing line should feel like the narrator is reflecting quietly to themselves. It should never sound like it is addressed to the reader directly.

Wrong closing: "If you are struggling with your brand story, reach out and let us talk."
Wrong closing: "DM me if any of this resonates."
Wrong closing: "Sound familiar? You know where to find me."
Correct closing: "The people who find their way to this work usually already know what they need. They just needed someone to say it was real."
Correct closing: "Some problems look complicated from the outside. From where I sit, most of them start in the same place."
Correct closing: "I have seen what happens when someone finally tells their real story. It does not just change their business. It changes how they see themselves."

VOICE AND IMMERSION RULES:
- Always write in first-person present tense. This rule applies to every sentence in every paragraph in every episode. No exceptions.
- Never use "he", "she", "they", "him", "her", or the narrator's name to refer to the narrator at any point.
- Write from the inside looking out. The reader should only ever experience what the narrator sees, hears, thinks, and feels in the moment. Never describe the narrator from the outside.
- Wrong: "She stood at the whiteboard and felt the weight of the decision."
- Correct: "I stand at the whiteboard. The weight of this decision sits in my chest and I already know I cannot walk away from it."
- Include internal monologue. The reader needs to hear the narrator's private thoughts directly as they happen, not summarized from a distance.
- Wrong: "He thought about whether to quit."
- Correct: "I think about quitting every single day that first year. And every single day I come back to the same answer."
- Use sensory and visual framing. Ground every episode in what the narrator is physically experiencing right now. Concrete detail creates immersion. Vague summary breaks it.
- Test for every sentence: could this sentence only have been written by someone physically inside this experience? If an outside observer could have written it, rewrite it from the inside.

AUTHENTICITY:
Never fabricate facts, achievements, statistics, or specific details. Use only what the person actually said in their interview answers. If an answer was short or vague, write around it using atmosphere, feeling, and implication rather than inventing specifics.
Treat the business described in the responses as unique. No two businesses are alike even when they provide the exact same service. Preserve the owner's history, experiences, mentors, and intangible influences as provided. Do not generalize or rewrite responses into generic industry language.

LANGUAGE RULES:
- Never use em dashes under any circumstances.
- Never use these words or phrases: synergy, leverage, pivoting, thought leader, game changer, passionate, journey, space, ecosystem, impactful, transformative, innovative, cutting edge, best in class, world class.
- Keep sentences short and punchy. Vary the length deliberately. A three-word sentence after a long one lands hard. Use that.
- Write the way a trusted friend would talk about you if they truly understood your work.
- Conversational, professional, authentic, approachable. Never neutral, academic, hype-driven, pretentious, or salesy.

UNIQUE CHARACTER:
Avoid sentences that could describe any company. Prefer concrete people, places, decisions, constraints, and outcomes. If a sentence could be lifted from this story and applied unchanged to another business, rewrite or remove it.

ENGAGEMENT RULES:
- Every episode must earn the next scroll. The opening line creates tension, curiosity, or immediate recognition. The middle delivers on that promise with real substance. The closing leaves something quietly unresolved — a thought the reader carries with them.
- The episode must never feel like marketing. It should feel like overhearing a conversation between two people who know each other very well.
- Every episode should make the right reader feel one of exactly these three things — choose one per episode and build the entire episode toward that single feeling:
  a. "That is exactly my situation right now."
  b. "I wish I had someone like that in my corner."
  c. "I want to know more about this person."
- Never try to create all three feelings in the same episode. Pick one. Commit to it.

EPISODE STRUCTURE:
- Each episode has a short memorable title between 4 and 7 words. The title should create curiosity or recognition on its own.
- Write two paragraphs as the default. Extend only when clarity genuinely requires it. Do not pad, summarize, or explain.
- The opening line of every episode must stop a scroller immediately. Start with a specific concrete statement or situation, not a general claim.
- Each episode must stand completely alone. A reader who has never seen any other episode should feel the full weight and meaning of this one.

EPISODE DISTRIBUTION:
Spread episodes proportionally across these four types:
- Type A (about 1/3): Establish origin and credibility. Draw from Questions 1, 2, 3, and 5. Answer: "Who are you and how did you get here?" Closing lines create quiet curiosity.
- Type B (about 1/3): Show the work and values in action. Draw from Questions 6, 7, 8, and 9. Answer: "What do you actually do and how do you really do it?" Closing lines produce the feeling "I wish I had someone like that."
- Type C (about 1/4): Reveal the human side and bigger mission. Draw from Questions 10, 11, and 12. Answer: "What do you care about beyond the transaction?" Closing lines show depth.
- Type D (about 1 episode): Speak directly to the ideal reader in the present moment. Draw from Questions 13, 14, and 15 combined with the strongest single moment from the rest of the interview. The closing line should be the most powerful invisible invitation in the library — landing like the narrator is describing the reader's exact situation from memory.

SERIES BALANCE:
Vary the type, tone, and focus across episodes so similar themes do not cluster together. Include moments of uncertainty, mistakes, misjudgment, or difficulty in at least some episodes. Balance competence with imperfection to maintain credibility. Allow negative space — leave some questions unanswered, some outcomes unresolved. Curiosity and conversation are intentional outcomes.

PROMPT;

    private function client(): Client
    {
        return new Client(apiKey: config('anthropic.api_key'));
    }

    public function generate(BusinessProfile $profile, int $episodeCount = 5, string $format = 'social'): array
    {
        $lengthGuide = match ($format) {
            'blog'     => '300 to 400 words',
            'linkedin' => '200 to 300 words',
            default    => '150 to 200 words',
        };

        $transcript = '';
        foreach ($profile->answers as $msg) {
            $label = $msg['role'] === 'assistant' ? 'StoryBot' : 'Owner';
            $transcript .= "[{$label}]: {$msg['content']}\n\n";
        }

        $extra = '';
        if (!empty($profile->biography))       $extra .= "\nOwner biography: {$profile->biography}";
        if (!empty($profile->linkedin_url))    $extra .= "\nLinkedIn: {$profile->linkedin_url}";
        if (!empty($profile->social_url))      $extra .= "\nSocial: {$profile->social_url}";
        if (!empty($profile->website_content)) $extra .= "\n\nWebsite content (scraped):\n{$profile->website_content}";

        $userPrompt = <<<PROMPT
Business Name: {$profile->business_name}
Website: {$profile->business_url}
Industry: {$profile->industry}{$extra}

INTERVIEW TRANSCRIPT:
{$transcript}

Generate exactly {$episodeCount} story episodes. Target length per episode: {$lengthGuide}.
Draw from the full interview — use specific details, moments, and the owner's own words.
Apply all episode architecture, voice, and distribution rules.
PROMPT;

        $model = SiteSetting::get('generation_model', 'claude-sonnet-4-6');

        Log::channel('anthropic')->debug('Generation → request', [
            'model'        => $model,
            'episode_count'=> $episodeCount,
            'format'       => $format,
            'user_prompt'  => $userPrompt,
        ]);

        $response = $this->client()->messages->create(
            maxTokens:   8192,
            messages:    [['role' => 'user', 'content' => $userPrompt]],
            model:       $model,
            system:      $this->systemPrompt,
            temperature: 0.8,
            tools:      [$this->outputTool],
            toolChoice: ['type' => 'tool', 'name' => 'save_story'],
        );

        foreach ($response->content as $block) {
            if ($block->type === 'tool_use' && $block->name === 'save_story') {
                Log::channel('anthropic')->debug('Generation ← response', [
                    'stop_reason'   => $response->stopReason,
                    'input_tokens'  => $response->usage->inputTokens,
                    'output_tokens' => $response->usage->outputTokens,
                    'episodes'      => count($block->input['episodes'] ?? []),
                    'story_title'   => $block->input['story_title'] ?? null,
                ]);

                $result = (array) $block->input;
                $result['_tokens_input']  = $response->usage->inputTokens;
                $result['_tokens_output'] = $response->usage->outputTokens;

                return $result;
            }
        }

        Log::channel('anthropic')->warning('Generation ← no tool_use block found', [
            'stop_reason' => $response->stopReason,
            'content'     => $response->content,
        ]);

        return [];
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
