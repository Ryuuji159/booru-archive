<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'discovered_posts_count',
        'started_at',
        'finished_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'discovered_posts_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected function parametersSummary(): Attribute
    {
        return Attribute::get(function (): string {
            $tags = array_values(array_filter(data_get($this->parameters, 'tags', [])));

            if ($tags === []) {
                return '[]';
            }

            $preview = array_slice($tags, 0, 3);
            $suffix = count($tags) > 3 ? ' ...' : '';

            return 'tags: '.implode(', ', $preview).$suffix;
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
