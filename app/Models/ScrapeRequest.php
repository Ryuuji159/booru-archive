<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'site',
        'parameters',
        'status',
        'requested_posts_count',
        'discovered_posts_count',
        'started_at',
        'finished_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'requested_posts_count' => 'integer',
            'discovered_posts_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
