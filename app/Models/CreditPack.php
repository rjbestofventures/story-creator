<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPack extends Model
{
    protected $fillable = ['slug', 'label', 'price', 'episode_limit', 'revision_credits', 'stripe_price_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function userCredits(): HasMany
    {
        return $this->hasMany(UserCredit::class);
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
