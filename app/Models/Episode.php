<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    protected $fillable = [
        'story_id',
        'episode_number',
        'title',
        'content',
        'format',
        'status',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EpisodeVersion::class)->orderByDesc('version');
    }
}
