<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCredit extends Model
{
    protected $fillable = [
        'user_id', 'credit_pack_id', 'episode_limit', 'revision_credits_granted',
        'stripe_checkout_session_id', 'status', 'purchased_at',
    ];

    protected function casts(): array
    {
        return ['purchased_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditPack(): BelongsTo
    {
        return $this->belongsTo(CreditPack::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeSpent($query)
    {
        return $query->where('status', 'spent');
    }
}
