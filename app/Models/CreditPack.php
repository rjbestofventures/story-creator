<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPack extends Model
{
    protected $fillable = ['slug', 'label', 'stories_count', 'price', 'episode_limit', 'revision_credits', 'stripe_price_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'stories_count' => 'integer',
        ];
    }

    public function userCredits(): HasMany
    {
        return $this->hasMany(UserCredit::class);
    }

    /**
     * Grant this pack to a user: create one story credit per story the pack
     * includes, and add the pack's revision credits to the user's wallet once.
     */
    public function grantTo(User $user, ?string $stripeSessionId = null): void
    {
        $count = max(1, $this->stories_count);
        $purchasedAt = now();

        for ($i = 0; $i < $count; $i++) {
            UserCredit::create([
                'user_id' => $user->id,
                'credit_pack_id' => $this->id,
                'episode_limit' => $this->episode_limit,
                'revision_credits_granted' => $i === 0 ? $this->revision_credits : 0,
                'stripe_checkout_session_id' => $i === 0 ? $stripeSessionId : null,
                'status' => 'available',
                'purchased_at' => $purchasedAt,
            ]);
        }

        $user->increment('refine_credits', $this->revision_credits);
    }

    public function formattedPrice(): string
    {
        return '$'.number_format($this->price / 100, 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
