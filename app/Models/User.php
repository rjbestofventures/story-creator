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

#[Fillable(['name', 'email', 'password', 'is_active', 'credits', 'is_verified_partner'])]
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
            'credits' => 'integer',
            'is_verified_partner' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class)->latestOfMany();
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class)->latest();
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(UserCredit::class)->latest('purchased_at');
    }

    // -------------------------------------------------------------------------
    // Credit helpers
    // -------------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    public function canCreateStory(): bool
    {
        return $this->isAdmin() || $this->credits > 0;
    }

    public function canRefine(): bool
    {
        return $this->isAdmin() || $this->credits > 0;
    }

    /**
     * True once the user has bought (or been granted) at least one main pack.
     * Used to gate the Credit Boost add-on.
     */
    public function hasBoughtMainPack(): bool
    {
        return $this->purchases()
            ->whereHas('creditPack', fn ($q) => $q->where('type', '!=', 'addon'))
            ->exists();
    }
}
