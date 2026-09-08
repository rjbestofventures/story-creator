<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CreditPack extends Model
{
    protected $fillable = ['slug', 'label', 'type', 'credits', 'max_episodes', 'price', 'stripe_price_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credits' => 'integer',
            'max_episodes' => 'integer',
        ];
    }

    public function userCredits(): HasMany
    {
        return $this->hasMany(UserCredit::class);
    }

    /** A pack bought as a primary purchase, as opposed to a top-up add-on. */
    public function isMainPack(): bool
    {
        return $this->type !== 'addon';
    }

    /**
     * The single funnel through which acquiring a pack takes effect: it records
     * the ledger entry, adds the credits, confers partner status where the pack
     * is partner-priced, and ends any running trial. Purchase, checkout success,
     * admin grant, and provisioning all route through here, so no caller needs
     * its own handling and none can drift from the others.
     *
     * Ending a trial unlocks the member's whole library, because lock state is
     * derived from trial state rather than stored on each episode.
     */
    public function grantTo(User $user, ?string $stripeSessionId = null, ?int $amountPaid = null): void
    {
        DB::transaction(function () use ($user, $stripeSessionId, $amountPaid) {
            UserCredit::create([
                'user_id' => $user->id,
                'credit_pack_id' => $this->id,
                'credits_granted' => $this->credits,
                'amount_paid' => $amountPaid,
                'source' => $stripeSessionId ? 'online' : 'grant',
                'stripe_checkout_session_id' => $stripeSessionId,
                'purchased_at' => now(),
            ]);

            $user->increment('credits', $this->credits);

            if ($this->type === 'partner') {
                $user->forceFill(['is_verified_partner' => true])->save();
            }

            if ($this->isMainPack()) {
                $user->endTrial();
            }
        });
    }

    public function formattedPrice(): string
    {
        return '$'.number_format($this->price / 100, 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * The main-pack type a given user should see in the shop.
     * Centralized so partner pricing can be exposed publicly later by
     * changing this single rule.
     */
    public static function audienceType(?User $user): string
    {
        return $user?->is_verified_partner ? 'partner' : 'storybot';
    }
}
