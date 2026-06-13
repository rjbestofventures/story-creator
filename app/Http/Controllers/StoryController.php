<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateStory;
use App\Models\BusinessProfile;
use App\Models\Episode;
use App\Models\EpisodeVersion;
use App\Models\SiteSetting;
use App\Models\Story;
use App\Services\InterviewService;
use App\Services\StoryGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class StoryController extends Controller
{
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
        $sub = $user->activeSubscription?->load('plan');
        $plan = $sub?->plan;

        return Inertia::render('Stories/Index', [
            'stories' => $stories,
            'profile' => $profile,
            'subscription' => $sub,
            'plan' => $plan,
            'isAdmin' => $user->isAdmin(),
            'adminRole' => $user->hasRole('super_admin') ? 'super_admin' : ($user->hasRole('admin') ? 'admin' : null),
        ]);
    }

    // -------------------------------------------------------------------------
    // Onboarding / interview wizard
    // -------------------------------------------------------------------------

    public function create(Request $request)
    {
        $episodeLimit = $request->user()->activeSubscription?->effectiveEpisodeLimit() ?? 5;

        return Inertia::render('Stories/Create', [
            'profile' => null,
            'story' => null,
            'episode_limit' => $episodeLimit,
        ]);
    }

    // Resume an in-progress interview
    public function resume(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);
        abort_unless(in_array($story->status, ['interviewing', 'interview_complete']), 404);

        $story->load('businessProfile');

        $episodeLimit = $story->is_demo
            ? 3
            : ($request->user()->activeSubscription?->effectiveEpisodeLimit() ?? 5);

        return Inertia::render('Stories/Create', [
            'profile' => $story->businessProfile,
            'episode_limit' => $episodeLimit,
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

        $data = $request->validate(['format' => 'in:social,blog,linkedin']);
        $format = $data['format'] ?? 'social';

        $user = $request->user();

        if (! $user->isAdmin()) {
            abort_unless($user->canCreateStory(), 403, 'You have no story credits remaining.');
            $user->activeSubscription->decrement('story_credits');
        }

        $story->update(['status' => 'generating']);
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
        ]);

        $user = $request->user();

        if (! $user->isAdmin()) {
            abort_unless($user->canCreateStory(), 403, 'You have no story credits remaining.');
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
        ]);

        if (! $user->isAdmin()) {
            $user->activeSubscription->decrement('story_credits');
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
                ]),
            ],
            'canCreateStory' => $user->canCreateStory(),
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
            abort_unless($user->canRefine(), 403, 'You have no refine credits remaining.');
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
        $generator = app(StoryGeneratorService::class);
        $generated = $generator->generate($profile, 1, $format);

        $ep = $generated['episodes'][0] ?? null;
        if ($ep) {
            $episode->update(['title' => $ep['title'], 'content' => $ep['content']]);
        }

        $story->increment('refines_used');
        $story->increment('tokens_input', $generated['_tokens_input'] ?? 0);
        $story->increment('tokens_output', $generated['_tokens_output'] ?? 0);

        if (! $user->isAdmin()) {
            $user->activeSubscription->decrement('refine_credits');
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

        $user = $request->user();

        if (! $user->isAdmin()) {
            abort_unless($user->canRefine(), 403, 'You have no refine credits remaining.');
        }

        // Save current as a version before restoring
        $nextVersion = $episode->versions()->max('version') ?? 0;
        EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version' => $nextVersion + 1,
            'title' => $episode->title,
            'content' => $episode->content,
        ]);

        $episode->update(['title' => $version->title, 'content' => $version->content]);

        if (! $user->isAdmin()) {
            $user->activeSubscription->decrement('refine_credits');
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

    // -------------------------------------------------------------------------
    // Inline edit — save title / content edits made directly in the card
    // -------------------------------------------------------------------------

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
            abort_unless($user->canRefine(), 403, 'You have no refine credits remaining.');
        }

        $data = $request->validate([
            'tone' => 'required|in:friendlier,shorter,humor,professional',
        ]);

        $nextVersion = $episode->versions()->max('version') ?? 0;
        EpisodeVersion::create([
            'episode_id' => $episode->id,
            'version' => $nextVersion + 1,
            'title' => $episode->title,
            'content' => $episode->content,
        ]);

        $generator = app(StoryGeneratorService::class);
        $refined = $generator->refineTone($episode->content, $data['tone']);

        $episode->update(['content' => $refined['content']]);

        $story->increment('tokens_input', $refined['_tokens_input'] ?? 0);
        $story->increment('tokens_output', $refined['_tokens_output'] ?? 0);

        if (! $user->isAdmin()) {
            $user->activeSubscription->decrement('refine_credits');
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
}
