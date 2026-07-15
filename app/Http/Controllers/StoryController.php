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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
                ? "{$count}-chapter stories require the {$unlock}."
                : "Your current pack does not allow {$count}-chapter stories.",
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
            'canCreateStory' => $user->canCreateStory(),
            'isAdmin' => $user->isAdmin(),
            'credits' => $user->isAdmin() ? null : $user->credits,
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
}
