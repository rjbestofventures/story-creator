<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class)->latest();
    }

    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class)->latestOfMany();
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class)->latest();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->whereIn('status', ['active', 'trialing'])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latestOfMany();
    }

    // -------------------------------------------------------------------------
    // Subscription helpers
    // -------------------------------------------------------------------------

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription !== null;
    }

    public function canCreateStory(): bool
    {
        return $this->activeSubscription?->canCreateStory() ?? false;
    }

    public function canRefine(): bool
    {
        return $this->activeSubscription?->canRefine() ?? false;
    }
}
