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

    /** How long a trial library stays readable before it is hidden again. */
    public const TRIAL_VISIBILITY_DAYS = 30;

    /** How long after a member reactivates before the team is told. */
    public const REACTIVATION_NOTICE_HOURS = 24;

    protected $fillable = [
        'user_id',
        'business_profile_id',
        'title',
        'status',
        'is_demo',
        'created_on_trial',
        'episodes_unlocked_at',
        'episodes_reactivated_at',
        'reactivation_notified_at',
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
     * Derived from the owner's trial state, so buying a pack unlocks every
     * story at once — except that paying to unlock this one story is recorded
     * here and survives the account staying on trial.
     */
    public function locksEpisodes(): bool
    {
        return (bool) $this->user?->is_trial && $this->episodes_unlocked_at === null;
    }

    /** Cost in credits to open the episodes this story still withholds. */
    public function unlockCost(): int
    {
        if (! $this->locksEpisodes()) {
            return 0;
        }

        return max(0, $this->episodes()->count() - self::TRIAL_UNLOCKED_EPISODES);
    }

    /**
     * A trial library goes quiet a month after the member signed up, and stays
     * quiet until they ask for it back.
     */
    public function hidesReadableEpisodes(): bool
    {
        if (! $this->created_on_trial || ! $this->user?->spendsTrialAllowance()) {
            return false;
        }

        if ($this->episodes_reactivated_at !== null) {
            return false;
        }

        return $this->user->created_at?->addDays(self::TRIAL_VISIBILITY_DAYS)->isPast() ?? false;
    }

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'created_on_trial' => 'boolean',
            'episodes_unlocked_at' => 'datetime',
            'episodes_reactivated_at' => 'datetime',
            'reactivation_notified_at' => 'datetime',
        ];
    }
}
