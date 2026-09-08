<?php

namespace App\Policies;

use App\Models\Episode;
use App\Models\User;

/**
 * The single place the per-episode guards live. Ownership, demo stories, and
 * locked episodes were previously three separate inline checks repeated across
 * every per-episode endpoint; consolidating them here means an endpoint added
 * later inherits all three rather than remembering them.
 */
class EpisodePolicy
{
    /** Read an episode's content — its text, its versions, or its audio. */
    public function view(User $user, Episode $episode): bool
    {
        return $this->owns($user, $episode) && ! $episode->isLocked();
    }

    /** Change an episode, or spend AI on it. */
    public function modify(User $user, Episode $episode): bool
    {
        return $this->owns($user, $episode)
            && ! $episode->story->is_demo
            && ! $episode->isLocked();
    }

    private function owns(User $user, Episode $episode): bool
    {
        return $episode->story->user_id === $user->id;
    }
}
