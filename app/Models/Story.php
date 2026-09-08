<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    use HasFactory;

    /** Episodes in a trial member's library. Matches the smallest purchasable tier. */
    public const TRIAL_EPISODE_COUNT = 12;

    /** How many of those a trial member may read before unlocking. */
    public const TRIAL_UNLOCKED_EPISODES = 3;

    protected $fillable = [
        'user_id',
        'business_profile_id',
        'title',
        'status',
        'is_demo',
        'episode_limit',
        'refines_used',
        'tokens_input',
        'tokens_output',
        'tokens_interview_input',
        'tokens_interview_output',
        'interview_model',
        'generation_model',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }

    /**
     * Whether episodes in this story are withheld beyond the unlocked few.
     * Lock state is derived from the owner's trial state rather than stored, so
     * ending a trial unlocks every episode at once with no sweep or migration.
     */
    public function locksEpisodes(): bool
    {
        return (bool) $this->user?->is_trial;
    }
}
