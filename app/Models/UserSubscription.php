<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'billing_interval',
        'starts_at',
        'expires_at',
        'billing_period_ends_at',
        'story_credits',
        'refine_credits',
        'episode_limit',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'billing_period_ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function effectiveEpisodeLimit(): int
    {
        return $this->episode_limit > 0 ? $this->episode_limit : $this->plan->episode_limit;
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active' && $this->status !== 'trialing') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function canCreateStory(): bool
    {
        return $this->isActive() && $this->story_credits > 0;
    }

    public function canRefine(): bool
    {
        return $this->isActive() && $this->refine_credits > 0;
    }
}
