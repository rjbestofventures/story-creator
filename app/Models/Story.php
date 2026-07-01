<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_profile_id',
        'title',
        'status',
        'is_demo',
        'episode_limit',
        'refines_used',
        'tokens_input',
        'tokens_output',
        'tokens_interview_input',
        'tokens_interview_output',
        'interview_model',
        'generation_model',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }
}
