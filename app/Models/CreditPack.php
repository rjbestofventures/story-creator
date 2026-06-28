<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPack extends Model
{
    protected $fillable = ['slug', 'label', 'type', 'credits', 'price', 'stripe_price_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credits' => 'integer',
        ];
    }

    public function userCredits(): HasMany
    {
        return $this->hasMany(UserCredit::class);
    }

    /**
     * Grant this pack to a user: record a ledger entry and add its credits
     * to the user's wallet.
     */
    public function grantTo(User $user, ?string $stripeSessionId = null, ?int $amountPaid = null): void
    {
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
