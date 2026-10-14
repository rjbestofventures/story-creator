<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    use Prunable;

    public const RETENTION_DAYS = 14;

    public const UPDATED_AT = null;

    protected $fillable = ['method', 'path', 'status', 'duration_ms', 'ip', 'user_id', 'user_agent', 'blocked'];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'duration_ms' => 'integer',
            'blocked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    /** Narrow the log by the admin page's filters: status class, method, IP and path text. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, function (Builder $q, string $status) {
                if ($status === 'blocked') {
                    return $q->where('blocked', true);
                }
                $floor = (int) $status[0] * 100;

                return $q->whereBetween('status', [$floor, $floor + 99]);
            })
            ->when($filters['method'] ?? null, fn (Builder $q, string $method) => $q->where('method', strtoupper($method)))
            ->when($filters['ip'] ?? null, fn (Builder $q, string $ip) => $q->where('ip', $ip))
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where('path', 'like', '%'.addcslashes($search, '%_\\').'%'));
    }
}
