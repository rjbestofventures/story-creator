<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'custom_refine_instruction',
    ];

    protected function title(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : self::stripDashes($value),
        );
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : self::stripDashes($value),
        );
    }

    /**
     * Em, en, and other dashes read as AI-written. Collapse any dash used as a
     * sentence separator into natural punctuation so episodes stay human.
     */
    public static function stripDashes(string $text): string
    {
        return preg_replace('/\s*[\x{2012}\x{2013}\x{2014}\x{2015}\x{2E3A}\x{2E3B}]+\s*/u', ', ', $text);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EpisodeVersion::class)->orderByDesc('version');
    }
}
