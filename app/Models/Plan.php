<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'episode_limit',
        'stories_per_month',
        'refine_monthly',
        'price_monthly',
        'price_yearly',
        'stripe_price_monthly',
        'stripe_price_yearly',
        'trial_months',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function stripePriceId(string $interval): ?string
    {
        return $interval === 'yearly' ? $this->stripe_price_yearly : $this->stripe_price_monthly;
    }

    public function isFree(): bool
    {
        return $this->price_monthly === 0;
    }

    public function monthlyPrice(): string
    {
        return $this->price_monthly === 0 ? 'Free' : '$'.$this->price_monthly.'/ mo';
    }
}
