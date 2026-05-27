<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Story;
use App\Services\InterviewService;
use App\Services\StoryGeneratorService;
use Illuminate\Http\Request;
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
            ->with(['businessProfile', 'episodes'])
            ->withCount('episodes')
            ->get();

        $profile = $user->businessProfile;
        $sub     = $user->activeSubscription?->load('plan');
        $plan    = $sub?->plan;

        return Inertia::render('Stories/Index', [
            'stories'      => $stories,
            'profile'      => $profile,
            'subscription' => $sub,
            'plan'         => $plan,
        ]);
    }

    // -------------------------------------------------------------------------
    // Onboarding / interview wizard
    // -------------------------------------------------------------------------

    public function create(Request $request)
    {
        $profile = $request->user()->businessProfile;

        return Inertia::render('Stories/Create', [
            'profile' => $profile,
        ]);
    }

    // -------------------------------------------------------------------------
    // AI interview — returns Claude's next question as JSON
    // -------------------------------------------------------------------------

    public function interview(Request $request)
    {
        $data = $request->validate([
            'messages'          => 'required|array',
            'messages.*.role'   => 'required|in:user,assistant',
            'messages.*.content'=> 'required|string',
            'business_name'     => 'required|string|max:120',
            'business_url'      => 'nullable|string|max:255',
            'industry'          => 'nullable|string|max:80',
        ]);

        $result = (new InterviewService())->getNextMessage(
            $data['messages'],
            [
                'business_name' => $data['business_name'],
                'business_url'  => $data['business_url'] ?? '',
                'industry'      => $data['industry'] ?? '',
            ]
        );

        return response()->json($result);
    }

    // -------------------------------------------------------------------------
    // Generate story from completed interview
    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name'     => 'required|string|max:120',
            'business_url'      => 'nullable|url|max:255',
            'industry'          => 'nullable|string|max:80',
            'messages'          => 'required|array|min:2',
            'messages.*.role'   => 'required|in:user,assistant',
            'messages.*.content'=> 'required|string',
            'episode_count'     => 'integer|min:1|max:10',
            'format'            => 'in:social,blog,linkedin',
        ]);

        $user = $request->user();

        $profile = BusinessProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $data['business_name'],
                'business_url'  => $data['business_url'] ?? null,
                'industry'      => $data['industry'] ?? null,
                'answers'       => $data['messages'], // store full conversation
            ]
        );

        $count  = $data['episode_count'] ?? 5;
        $format = $data['format'] ?? 'social';

        $generator = new StoryGeneratorService();
        $generated = $generator->generate($profile, $count, $format);
        $story     = $generator->saveToStory($profile, $generated, $format);

        $sub = $user->activeSubscription;
        if ($sub && $sub->story_credits > 0) {
            $sub->decrement('story_credits');
        }

        return to_route('stories.show', $story->id);
    }

    // -------------------------------------------------------------------------
    // Show a single story + its episodes
    // -------------------------------------------------------------------------

    public function show(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id, 403);

        $story->load(['episodes', 'businessProfile']);

        return Inertia::render('Stories/Show', [
            'story' => $story,
        ]);
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

        $data = $request->validate([
            'episode_number' => 'required|integer',
        ]);

        $profile   = $story->businessProfile;
        $format    = $story->episodes->first()?->format ?? 'social';
        $generator = new StoryGeneratorService();
        $generated = $generator->generate($profile, 1, $format);

        $ep = $generated['episodes'][0] ?? null;
        if ($ep) {
            $story->episodes()
                ->where('episode_number', $data['episode_number'])
                ->update(['title' => $ep['title'], 'content' => $ep['content']]);
        }

        $sub = $request->user()->activeSubscription;
        if ($sub && $sub->refine_credits > 0) {
            $sub->decrement('refine_credits');
        }

        return back();
    }
}
