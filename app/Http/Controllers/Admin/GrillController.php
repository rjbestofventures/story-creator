<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GrillController extends Controller
{
    /**
     * List every story that has an interview transcript, searchable by user or
     * business name. Selecting one opens its question/answer review.
     */
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $query = Story::query()
            ->with(['user:id,name,email', 'businessProfile:id,business_name,answers'])
            ->whereHas('businessProfile', fn ($p) => $p
                ->whereNotNull('answers')
                ->where('answers', '!=', '[]'))
            ->latest();

        if ($q !== '') {
            $query->where(function ($outer) use ($q) {
                $outer->whereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('businessProfile', fn ($p) => $p
                        ->where('business_name', 'like', "%{$q}%"));
            });
        }

        $interviews = $query->paginate(20)->withQueryString()->through(fn (Story $story) => [
            'id' => $story->id,
            'title' => $story->title,
            'status' => $story->status,
            'created_at' => $story->created_at->format('n/j/Y'),
            'business_name' => $story->businessProfile?->business_name,
            'answered' => $story->businessProfile?->answeredCount() ?? 0,
            'user' => [
                'name' => $story->user->name,
                'email' => $story->user->email,
            ],
        ]);

        return Inertia::render('Admin/Grill/Index', [
            'interviews' => $interviews,
            'filters' => ['q' => $q],
        ]);
    }

    /**
     * Review the parsed question/answer pairs for a single interview.
     */
    public function show(Request $request, Story $story): Response
    {
        $story->load(['user:id,name,email', 'businessProfile']);

        $profile = $story->businessProfile;

        return Inertia::render('Admin/Grill/Show', [
            'interview' => [
                'story_id' => $story->id,
                'title' => $story->title,
                'status' => $story->status,
                'created_at' => $story->created_at->format('F j, Y'),
                'business_name' => $profile?->business_name,
                'industry' => $profile?->industry,
                'business_url' => $profile?->business_url,
                'linkedin_url' => $profile?->linkedin_url,
                'social_url' => $profile?->social_url,
                'biography' => $profile?->biography,
                'services' => $profile?->services,
                'user' => [
                    'name' => $story->user->name,
                    'email' => $story->user->email,
                ],
                'pairs' => $profile?->interviewQaPairs() ?? [],
            ],
        ]);
    }
}
