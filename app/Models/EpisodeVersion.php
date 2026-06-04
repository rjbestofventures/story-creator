<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpisodeVersion extends Model
{
    protected $fillable = ['episode_id', 'version', 'title', 'content'];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
