<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateStory;
use App\Models\BusinessProfile;
use App\Models\CreditPack;
use App\Models\Episode;
use App\Models\EpisodeVersion;
use App\Models\SiteSetting;
use App\Models\Story;
use App\Models\User;
use App\Services\InterviewService;
use App\Services\StoryGeneratorService;
use App\Services\TranscriptionService;
use App\Services\Tts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StoryController extends Controller
{
    /** Episode-count choices offered at generation; each episode costs 1 credit. */
    public const EPISODE_OPTIONS = [12, 18, 24];

    /**
     * Build the episode-count choices for a user, marking any above their pack
     * tier as locked and naming the pack that unlocks them.
     *
     * @return list<array{count:int,locked:bool,unlock_label:?string}>
     */
    private function episodeOptionsFor(User $user): array
    {
        $max = $user->maxEpisodes(); // null = unlimited (admins)

        $packs = CreditPack::query()
            ->active()
            ->ofType(CreditPack::audienceType($user))
            ->orderBy('max_episodes')
            ->get(['label', 'max_episodes']);

        return array_map(function (int $count) use ($max, $packs) {
            $locked = $max !== null && $count > $max;

            $unlock = $locked
                ? $packs->firstWhere('max_episodes', '>=', $count)?->label
                : null;

            return [
                'count' => $count,
                'locked' => $locked,
                'unlock_label' => $unlock,
            ];
        }, self::EPISODE_OPTIONS);
    }

    /**
     * Reject an episode count above the user's pack tier with a 422 naming the
     * pack that would unlock it.
     */
    private function assertWithinTier(User $user, int $count): void
    {
        $max = $user->maxEpisodes();

        if ($max === null || $count <= $max) {
            return;
        }

        $unlock = CreditPack::query()
            ->active()
            ->ofType(CreditPack::audienceType($user))
            ->where('max_episodes', '>=', $count)
            ->orderBy('max_episodes')
            ->value('label');

        throw ValidationException::withMessages([
            'episode_count' => $unlock
                ? "{$count}-episode stories require the {$unlock}."
                : "Your current pack does not allow {$count}-episode stories.",
        ]);
    }

    // -------------------------------------------------------------------------
    // Dashboard — user's story list
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $user = $request->user();

        $stories = $user->stories()
            ->where('is_demo', false)
            ->with(['businessProfile', 'episodes'])
            ->withCount('episodes')
            ->get();

        $profile = $user->businessProfile;

        return Inertia::render('Stories/Index', [
            'stories' => $stories,
            'profile' => $profile,
            'credits' => $user->isAdmin() ? null : $user->credits,
            'isAdmin' => $user->isAdmin(),
            'adminRole' => $user->hasRole('super_admin') ? 'super_admin' : ($user->hasRole('admin') ? 'admin' : null),
        ]);
    }

    // -------------------------------------------------------------------------
    // Onboarding / interview wizard
    // -------------------------------------------------------------------------

    public function create(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Stories/Create', [
            'profile' => null,
            'story' => null,
            'credits' => $user->isAdmin() ? null : $user->credits,
            'episode_options' => $this->episodeOptionsFor($user),
            'max_episodes' => $user->maxEpisodes(),
        ]);
    }

    // Resume an in-progress interview
    public function resume(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_unless(in_array($story->status, ['interviewing', 'interview_complete']), 404);

        $story->load('businessProfile');

        $user = $request->user();

        return Inertia::render('Stories/Create', [
            'profile' => $story->businessProfile,
            'credits' => $user->isAdmin() ? null : $user->credits,
            'episode_options' => $this->episodeOptionsFor($user),
            'max_episodes' => $user->maxEpisodes(),
            'story' => [
                'id' => $story->id,
                'status' => $story->status,
                'is_demo' => $story->is_demo,
                'messages' => $story->businessProfile->answers ?? [],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Init — create story + profile when interview starts
    // -------------------------------------------------------------------------

    public function init(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:120',
            'business_url' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:80',
            'biography' => 'nullable|string|max:1000',
            'services' => 'nullable|string|max:1000',
            'linkedin_url' => 'nullable|string|max:255',
            'social_url' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $websiteContent = null;
        if (! empty($data['business_url'])) {
            try {
                $url = $data['business_url'];
                if (! str_starts_with($url, 'http')) {
                    $url = 'https://'.$url;
                }
                $response = Http::timeout(6)->get($url);
                if ($response->successful()) {
                    $text = strip_tags($response->body());
                    $text = preg_replace('/\s+/', ' ', $text);
                    $websiteContent = trim(substr($text, 0, 3000));
                }
            } catch (\Throwable) {
                // non-critical — continue without website content
            }
        }

        $profile = BusinessProfile::create([
            'user_id' => $user->id,
            'business_name' => $data['business_name'],
            'business_url' => $data['business_url'] ?? null,
            'industry' => $data['industry'] ?? null,
            'biography' => $data['biography'] ?? null,
            'services' => $data['services'] ?? null,
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'social_url' => $data['social_url'] ?? null,
            'website_content' => $websiteContent,
            'answers' => [],
        ]);

        $story = Story::create([
            'user_id' => $user->id,
            'business_profile_id' => $profile->id,
            'title' => null,
            'status' => 'interviewing',
        ]);

        return response()->json(['story_id' => $story->id]);
    }

    // -------------------------------------------------------------------------
    // Save interview progress
    // -------------------------------------------------------------------------

    public function saveProgress(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'messages' => 'required|array',
            'status' => 'nullable|in:interviewing,interview_complete',
        ]);

        $story->businessProfile->update(['answers' => $data['messages']]);

        if (! empty($data['status'])) {
            $story->update(['status' => $data['status']]);
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Generate episodes for an existing story record
    // -------------------------------------------------------------------------

    public function retry(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_unless(in_array($story->status, ['failed', 'generating']), 422);

        $format = $story->episodes()->value('format') ?? 'social';

        $story->update(['status' => 'generating']);
        GenerateStory::dispatch($story, $format);

        return response()->json(['ok' => true]);
    }

    public function generate(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'format' => 'in:social,blog,linkedin',
            'episode_count' => 'required|integer|in:'.implode(',', self::EPISODE_OPTIONS),
        ]);
        $format = $data['format'] ?? 'social';
        $count = (int) $data['episode_count'];

        $user = $request->user();
        $this->assertWithinTier($user, $count);

        if (! $user->isAdmin()) {
            abort_if($user->credits < $count, 403, 'You don\'t have enough credits to generate this story.');
            $user->decrement('credits', $count);
        }

        $story->update(['status' => 'generating', 'episode_limit' => $count]);
        GenerateStory::dispatch($story, $format);

        return to_route('stories.show', $story->id);
    }

    // -------------------------------------------------------------------------
    // AI interview — returns Claude's next question as JSON
    // -------------------------------------------------------------------------

    public function interview(Request $request)
    {
        $data = $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
            'business_name' => 'required|string|max:120',
            'business_url' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:80',
        ]);

        $storyId = $request->input('story_id');

        $profile = $storyId
            ? Story::with('businessProfile')
                ->where('id', $storyId)
                ->where('user_id', $request->user()->id)
                ->first()
                ?->businessProfile
            : null;

        $result = (new InterviewService)->getNextMessage(
            $data['messages'],
            [
                'business_name' => $data['business_name'],
                'business_url' => $data['business_url'] ?? '',
                'industry' => $data['industry'] ?? '',
                'biography' => $profile?->biography ?? '',
                'services' => $profile?->services ?? '',
                'linkedin_url' => $profile?->linkedin_url ?? '',
                'social_url' => $profile?->social_url ?? '',
                'website_content' => $profile?->website_content ?? '',
            ]
        );

        if ($storyId) {
            Story::where('id', $storyId)
                ->where('user_id', $request->user()->id)
                ->update([
                    'interview_model' => SiteSetting::get('interview_model', 'claude-haiku-4-5-20251001'),
                    'tokens_interview_input' => \DB::raw('tokens_interview_input  + '.($result['_tokens_input'] ?? 0)),
                    'tokens_interview_output' => \DB::raw('tokens_interview_output + '.($result['_tokens_output'] ?? 0)),
                ]);
        }

        unset($result['_tokens_input'], $result['_tokens_output']);

        return response()->json($result);
    }

    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|max:25600',
        ]);

        try {
            $text = (new TranscriptionService)->transcribe($request->file('audio'));
        } catch (\Throwable) {
            return response()->json(['error' => 'Could not transcribe audio. Please try again or type your answer.'], 422);
        }

        return response()->json(['text' => $text]);
    }

    // -------------------------------------------------------------------------
    // Generate story from completed interview
    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:120',
            'business_url' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:80',
            'messages' => 'required|array|min:2',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
            'format' => 'in:social,blog,linkedin',
            'episode_count' => 'required|integer|in:'.implode(',', self::EPISODE_OPTIONS),
        ]);

        $user = $request->user();
        $count = (int) $data['episode_count'];
        $this->assertWithinTier($user, $count);

        if (! $user->isAdmin()) {
            abort_if($user->credits < $count, 403, 'You don\'t have enough credits to generate this story.');
        }

        $profile = BusinessProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $data['business_name'],
                'business_url' => $data['business_url'] ?? null,
                'industry' => $data['industry'] ?? null,
                'answers' => $data['messages'],
            ]
        );

        $format = $data['format'] ?? 'social';

        $story = Story::create([
            'user_id' => $user->id,
            'business_profile_id' => $profile->id,
            'title' => 'Generating…',
            'status' => 'generating',
            'episode_limit' => $count,
        ]);

        if (! $user->isAdmin()) {
            $user->decrement('credits', $count);
        }

        GenerateStory::dispatch($story, $format);

        return to_route('stories.show', $story->id);
    }

    // -------------------------------------------------------------------------
    // Show a single story + its episodes
    // -------------------------------------------------------------------------

    public function show(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);

        $story->load(['episodes.versions', 'businessProfile']);

        $user = $request->user();

        return Inertia::render('Stories/Show', [
            'story' => [
                'id' => $story->id,
                'title' => $story->title,
                'status' => $story->status,
                'is_demo' => $story->is_demo,
                'business_profile' => $story->businessProfile,
                'episodes' => $story->episodes->map(fn ($ep) => [
                    'id' => $ep->id,
                    'episode_number' => $ep->episode_number,
                    'title' => $ep->title,
                    'content' => $ep->content,
                    'format' => $ep->format,
                    'versions_count' => $ep->versions->count(),
                    'custom_refine_instruction' => $ep->custom_refine_instruction,
                ]),
            ],
            'isAdmin' => $user->isAdmin(),
            'credits' => $user->isAdmin() ? null : $user->credits,
        ]);
    }

    // -------------------------------------------------------------------------
    // Interview answers — owner-facing Q&A viewer
    // -------------------------------------------------------------------------

    public function answers(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);

        $story->load('businessProfile');
        $profile = $story->businessProfile;

        return Inertia::render('Stories/Answers', [
            'interview' => [
                'story_id' => $story->id,
                'title' => $story->title,
                'business_name' => $profile?->business_name,
                'industry' => $profile?->industry,
                'business_url' => $profile?->business_url,
                'linkedin_url' => $profile?->linkedin_url,
                'social_url' => $profile?->social_url,
                'biography' => $profile?->biography,
                'services' => $profile?->services,
                'pairs' => $profile?->interviewQaPairs() ?? [],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Lightweight status poll
    // -------------------------------------------------------------------------

    public function status(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);

        return response()->json(['status' => $story->status]);
    }

    // -------------------------------------------------------------------------
    // Text-to-voice — read an episode aloud via whichever provider is active
    // -------------------------------------------------------------------------

    public function speakEpisode(Request $request, Story $story, Episode $episode)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_unless($episode->story_id === $story->id, 404);

        $audio = Tts::speak(trim("{$episode->title}. {$episode->content}"), 'episode');

        return response($audio, 200, ['Content-Type' => 'audio/mpeg']);
    }

    /** Read arbitrary text aloud — used for individual chat bubbles in the interview. */
    public function speakText(Request $request)
    {
        $data = $request->validate([
            'text' => 'required|string|max:4096',
        ]);

        $audio = Tts::speak($data['text']);

        return response($audio, 200, ['Content-Type' => 'audio/mpeg']);
    }

    /**
     * The public /demo page has no auth, so its TTS endpoint only ever synthesizes
     * these exact, fixed lines (never arbitrary client-supplied text) and caches
     * each one to disk after the first request.
     */
    public const DEMO_LINES = [
        "Hi, I am your StoryCreator.Bot Assistant, or you can call me StoryBot! I'll ask you a few quick questions about Barnacle Busters, then turn your answers into a library of stories worth sharing. Ready?",
        "How did you get into this business? You don't need to make it polished. Just tell it like you would explain it to someone over coffee.",
        "There's something real about starting young, cleaning boats for coffee or a beer while everyone else was off partying. That kind of scrappy, hands-on beginning is exactly the origin story customers connect with.",
        'What is one experience that changed the way you run your business today? Think of one specific customer, mistake, problem, mentor, job, opportunity, or turning point that taught you something you still use now.',
        "That's a real turning point, understanding that going overboard for your customers meant the business had to grow beyond just you. And handing it to your son makes it a family story people will remember.",
        'What is something you believe about your business that you learned from experience?',
        'That is everything I need. Take care of people first and everything comes from that, that belief runs through your whole story. Give me a moment while I put your story library together.',
    ];

    /**
     * The customer's side of the demo interview. Kept separate from DEMO_LINES so
     * each side can be narrated in its own voice.
     */
    public const DEMO_ANSWER_LINES = [
        "I started cleaning boats for extra money while I was young in northeast. For coffee or a beer. And so while my friends were, you know, partying during the summer, I was cleaning boats. And, and yeah, that's how I started it.",
        "I realized that if I wanted to clean as many bottoms as I could and truly go overboard for my customers, that I would have to grow the business beyond myself. That it couldn't be me doing it. And then, as I got older and I got my family involved, it became even bigger. And now I'm proud to say my son, Rayan Danielle, is running it. And I think that answers that.",
        'If you take care of people first everything comes from that.',
    ];

    /**
     * The generated story-library episodes shown at the end of the demo. Narrated
     * in the same voice as the customer's answers — they're written in the
     * business owner's voice, same as those answers.
     */
    public const DEMO_EPISODE_LINES = [
        "It Started With a Bucket and a Beer. I was just a kid up northeast, cleaning boats for extra money. Nothing glamorous. I'd do it for coffee, or a beer, whatever someone wanted to hand me.\n\nAnd while my friends were off partying all summer, I was in the water, scrubbing hulls.\n\nI didn't know it at the time, but that was the beginning of Barnacle Busters. No business plan, no big vision. Just a kid who didn't mind getting in the water and doing the work nobody else wanted to do.\n\nThat's really how it started.\n\nWhat's something you started just to make a little extra money that turned into something bigger?",
        "Going Overboard Meant Growing Beyond Myself. At some point I realized something. If I wanted to clean as many bottoms as I could and truly go overboard for my customers, it couldn't just be me anymore.\n\nOne person only has so many hours, so many dives in a day.\n\nSo I started to grow the business beyond myself. And as I got older, I got my family involved, and it became even bigger than I ever pictured back when I was that kid with a bucket.\n\nNow I'm proud to say my son, Rayan Danielle, is running it.\n\nThat's the part I'm most proud of. Not the fleet, not the counties we cover. The family.\n\nWhat would it take for your business to grow beyond just you?",
        "Take Care of People First. After all these years, all the boats, all the divers, all the growth, here's what I believe more than anything.\n\nIf you take care of people first, everything comes from that.\n\nThe customers, the crew, my own family. Take care of them first, do right by them, and the rest follows. The vessels get serviced right at the dock. The work gets done by people who are trained and certified and actually care.\n\nThat's not a slogan. That's just how we've always done it, since the beginning.\n\nWhen you put people first, what have you seen come back to you?",
    ];

    public function speakDemo(Request $request)
    {
        $data = $request->validate([
            'text' => ['required', 'string', Rule::in([...self::DEMO_LINES, ...self::DEMO_ANSWER_LINES, ...self::DEMO_EPISODE_LINES])],
        ]);

        $isAnswer = in_array($data['text'], [...self::DEMO_ANSWER_LINES, ...self::DEMO_EPISODE_LINES], true);
        $role = $isAnswer ? 'demo_customer' : 'demo_bot';
        $voice = Tts::voiceFor($role);

        // Key the cache on provider and voice too — keying on text alone meant
        // changing the voice (or provider) in admin kept serving stale audio.
        $path = 'demo-audio/'.md5($data['text'].'|'.Tts::provider().'|'.$voice).'.mp3';

        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, Tts::speak($data['text'], $role));
        }

        return response(Storage::disk('local')->get($path), 200, ['Content-Type' => 'audio/mpeg']);
    }

    // -------------------------------------------------------------------------
    // Delete a story
    // -------------------------------------------------------------------------

    public function destroy(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);

        $story->delete();

        return to_route('stories.index');
    }

    // -------------------------------------------------------------------------
    // Regenerate a single episode
    // -------------------------------------------------------------------------

    public function regenerateEpisode(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_if($story->is_demo, 403);

        $user = $request->user();

        if (! $user->isAdmin()) {
            abort_unless($user->canRefine(), 403, 'You have no credits remaining.');
        }

        $data = $request->validate([
            'episode_number' => 'required|integer',
        ]);

        $episode = $story->episodes()->where('episode_number', $data['episode_number'])->firstOrFail();

        $nextVersion = $episode->versions()->max('version') ?? 0;
        EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version' => $nextVersion + 1,
            'title' => $episode->title,
            'content' => $episode->content,
        ]);

        $profile = $story->businessProfile;
        $format = $episode->format ?? 'social';
        $generator = new StoryGeneratorService;
        $generated = $generator->generate($profile, 1, $format);

        $ep = $generated['episodes'][0] ?? null;
        if ($ep) {
            $episode->update(['title' => $ep['title'], 'content' => $ep['content']]);
        }

        $story->increment('refines_used');
        $story->increment('tokens_input', $generated['_tokens_input'] ?? 0);
        $story->increment('tokens_output', $generated['_tokens_output'] ?? 0);

        if (! $user->isAdmin()) {
            $user->decrement('credits');
        }

        return response()->json([
            'episode' => [
                'id' => $episode->id,
                'episode_number' => $episode->episode_number,
                'title' => $episode->title,
                'content' => $episode->content,
                'format' => $episode->format,
            ],
        ]);
    }

    public function episodeVersions(Request $request, Story $story, Episode $episode)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_unless($episode->story_id === $story->id, 404);

        $versions = $episode->versions()->get()->map(fn ($v) => [
            'id' => $v->id,
            'version' => $v->version,
            'title' => $v->title,
            'preview' => mb_substr(strip_tags($v->content), 0, 120).'…',
            'content' => $v->content,
            'created_at' => $v->created_at->format('M j, g:i A'),
        ]);

        return response()->json(['versions' => $versions]);
    }

    public function restoreVersion(Request $request, Story $story, Episode $episode, EpisodeVersion $version)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_if($story->is_demo, 403);
        abort_unless($episode->story_id === $story->id, 404);
        abort_unless($version->episode_id === $episode->id, 404);

        // Save current as a version before restoring
        $nextVersion = $episode->versions()->max('version') ?? 0;
        EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version' => $nextVersion + 1,
            'title' => $episode->title,
            'content' => $episode->content,
        ]);

        $episode->update(['title' => $version->title, 'content' => $version->content]);

        return response()->json([
            'episode' => [
                'id' => $episode->id,
                'episode_number' => $episode->episode_number,
                'title' => $episode->title,
                'content' => $episode->content,
                'format' => $episode->format,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Inline edit — save title / content edits made directly in the card
    // -------------------------------------------------------------------------

    public function saveRefineInstruction(Request $request, Story $story, Episode $episode)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_if($story->is_demo, 403);
        abort_unless($episode->story_id === $story->id, 404);

        $data = $request->validate([
            'custom_refine_instruction' => 'nullable|string|max:2000',
        ]);

        $episode->update(['custom_refine_instruction' => $data['custom_refine_instruction']]);

        return response()->noContent();
    }

    public function updateEpisode(Request $request, Story $story, Episode $episode)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_if($story->is_demo, 403);
        abort_unless($episode->story_id === $story->id, 404);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string|max:50000',
        ]);

        $episode->update($data);

        return response()->json([
            'episode' => [
                'id' => $episode->id,
                'title' => $episode->title,
                'content' => $episode->content,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // AI Refine Tone — apply a tonal transformation to a single episode
    // -------------------------------------------------------------------------

    public function refineEpisodeTone(Request $request, Story $story, Episode $episode)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_if($story->is_demo, 403);
        abort_unless($episode->story_id === $story->id, 404);

        $user = $request->user();

        if (! $user->isAdmin()) {
            abort_unless($user->canRefine(), 403, 'You have no credits remaining.');
        }

        $data = $request->validate([
            'tone' => 'required|in:friendlier,shorter,humor,professional,longer,more_cta,less_cta,promotional,custom',
            'custom_instruction' => 'required_if:tone,custom|string|max:2000',
        ]);

        $nextVersion = $episode->versions()->max('version') ?? 0;
        EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version' => $nextVersion + 1,
            'title' => $episode->title,
            'content' => $episode->content,
        ]);

        $generator = new StoryGeneratorService;
        $refined = $generator->refineTone($episode->content, $data['tone'], $data['custom_instruction'] ?? null);

        $episode->update(['content' => $refined['content']]);

        $story->increment('refines_used');
        $story->increment('tokens_input', $refined['_tokens_input'] ?? 0);
        $story->increment('tokens_output', $refined['_tokens_output'] ?? 0);

        if (! $user->isAdmin()) {
            $user->decrement('credits');
        }

        return response()->json([
            'episode' => [
                'id' => $episode->id,
                'episode_number' => $episode->episode_number,
                'title' => $episode->title,
                'content' => $episode->content,
                'format' => $episode->format,
            ],
            'credits' => $user->isAdmin() ? null : $user->fresh()->credits,
        ]);
    }

    // -------------------------------------------------------------------------
    // Bulk AI Refine — apply one tonal transformation to several episodes at once
    // -------------------------------------------------------------------------

    public function bulkRefineEpisodes(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_if($story->is_demo, 403);

        $user = $request->user();

        $data = $request->validate([
            'episode_ids' => 'required|array|min:1',
            'episode_ids.*' => 'integer',
            'tone' => 'required|in:friendlier,shorter,humor,professional,longer,more_cta,less_cta,promotional,custom',
            'custom_instruction' => 'required_if:tone,custom|string|max:2000',
        ]);

        $episodes = $story->episodes()->whereIn('id', $data['episode_ids'])->get();
        abort_if($episodes->isEmpty(), 404);

        if (! $user->isAdmin()) {
            abort_unless($user->credits >= $episodes->count(), 403, 'Not enough credits to refine all selected episodes.');
        }

        $generator = new StoryGeneratorService;
        $updated = [];

        foreach ($episodes as $episode) {
            $nextVersion = $episode->versions()->max('version') ?? 0;
            EpisodeVersion::create([
                'episode_id' => $episode->id,
                'version' => $nextVersion + 1,
                'title' => $episode->title,
                'content' => $episode->content,
            ]);

            $refined = $generator->refineTone($episode->content, $data['tone'], $data['custom_instruction'] ?? null);
            $episode->update(['content' => $refined['content']]);

            $story->increment('refines_used');
            $story->increment('tokens_input', $refined['_tokens_input'] ?? 0);
            $story->increment('tokens_output', $refined['_tokens_output'] ?? 0);

            if (! $user->isAdmin()) {
                $user->decrement('credits');
            }

            $updated[] = [
                'id' => $episode->id,
                'episode_number' => $episode->episode_number,
                'title' => $episode->title,
                'content' => $episode->content,
                'format' => $episode->format,
            ];
        }

        return response()->json([
            'episodes' => $updated,
            'credits' => $user->isAdmin() ? null : $user->fresh()->credits,
        ]);
    }
}
