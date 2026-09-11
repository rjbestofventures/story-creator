<?php

namespace App\Models;

use App\Notifications\FirstLoginNotification;
use App\Notifications\VerifyEmailNotification;
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

#[Fillable(['name', 'email', 'password', 'is_active', 'credits', 'is_verified_partner', 'is_trial', 'trial_allowance'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasRoles, Notifiable;

    /** Stories a newly provisioned Trial Member may generate before converting. */
    public const DEFAULT_TRIAL_ALLOWANCE = 1;

    /** Credits a trial member is given when they convert to a partner. */
    public const PARTNER_CONVERSION_CREDITS = 45;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'tour_completed_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'credits' => 'integer',
            'is_verified_partner' => 'boolean',
            'is_trial' => 'boolean',
            'trial_allowance' => 'integer',
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
    // Notifications
    // -------------------------------------------------------------------------

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Marks this login and notifies the admin the first time it ever happens.
     */
    public function recordLogin(): void
    {
        if ($this->last_login_at === null) {
            FirstLoginNotification::sendFor($this);
        }

        $this->forceFill(['last_login_at' => now()])->save();
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

    /**
     * Whether generations still come out of the trial allowance. Becoming a
     * partner moves them onto credits — only their episode locks stay behind,
     * until they pay to open them.
     */
    public function spendsTrialAllowance(): bool
    {
        return $this->is_trial && ! $this->is_verified_partner;
    }

    public function canRefine(): bool
    {
        return $this->isAdmin() || $this->credits > 0;
    }

    /**
     * End this member's trial by paying for it — buying a main pack — which
     * opens every library they were still waiting on. Nothing is regenerated
     * and no content changes.
     *
     * Becoming a partner is the other way out of a trial; that one uses
     * becomePartner() and deliberately leaves the library shut.
     */
    public function endTrial(): void
    {
        if (! $this->is_trial) {
            return;
        }

        $this->forceFill(['is_trial' => false, 'trial_allowance' => 0])->save();

        $this->unlockTrialLibraries();
    }

    /** Open every library this member is still waiting on. Safe to repeat. */
    public function unlockTrialLibraries(): void
    {
        $this->stories()
            ->where('created_on_trial', true)
            ->whereNull('episodes_unlocked_at')
            ->update(['episodes_unlocked_at' => now()]);
    }

    /**
     * Confer partner status. The trial ends with it — they are a partner now,
     * not a trial member — but their library stays withheld until they spend
     * credits to open it.
     */
    public function becomePartner(): void
    {
        $this->forceFill([
            'is_verified_partner' => true,
            'is_trial' => false,
            'trial_allowance' => 0,
        ])->save();
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

    /**
     * The highest episode count this user may generate, unlocked by the most
     * recently purchased main pack (partner/storybot). Add-ons do not affect
     * tier. Downgrading (e.g. buying Basic after Premium) drops the limit back
     * down, since it always follows the most recent purchase. Falls back to
     * the Basic tier (12); null means unlimited (admins).
     */
    public function maxEpisodes(): ?int
    {
        if ($this->isAdmin()) {
            return null;
        }

        $latest = $this->purchases()
            ->whereHas('creditPack', fn ($q) => $q->whereIn('type', ['partner', 'storybot']))
            ->with('creditPack:id,max_episodes')
            ->first();

        return max(12, (int) ($latest?->creditPack?->max_episodes ?? 0));
    }

    /**
     * Label of the most recently purchased main pack (partner/storybot), or
     * null if the user has never bought/been granted one.
     */
    public function currentPackLabel(): ?string
    {
        $latest = $this->purchases()
            ->whereHas('creditPack', fn ($q) => $q->whereIn('type', ['partner', 'storybot']))
            ->with('creditPack:id,label')
            ->first();

        return $latest?->creditPack?->label;
    }
}
