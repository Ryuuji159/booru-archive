<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    public const MAX_DOWNLOAD_ATTEMPTS = 5;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DOWNLOADING = 'downloading';

    public const STATUS_DOWNLOADED = 'downloaded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'source_site',
        'source_post_id',
        'md5',
        'file_ext',
        'file_size',
        'width',
        'height',
        'rating',
        'score',
        'author',
        'source_created_at',
        'source_file_url',
        'source_preview_url',
        'storage_disk',
        'storage_path',
        'preview_path',
        'download_status',
        'download_attempts',
        'downloaded_at',
        'last_download_error',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'source_post_id' => 'integer',
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'score' => 'integer',
            'source_created_at' => 'datetime',
            'download_attempts' => 'integer',
            'downloaded_at' => 'datetime',
            'source_payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            if (blank($post->storage_disk)) {
                $post->storage_disk = (string) config('filesystems.media_disk', 'media');
            }
        });
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopePendingDownload(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('download_status', self::STATUS_PENDING)
                ->orWhere(function (Builder $query): void {
                    $query
                        ->where('download_status', self::STATUS_FAILED)
                        ->where('download_attempts', '<', self::MAX_DOWNLOAD_ATTEMPTS);
                });
        });
    }

    public function scopeReadyForGallery(Builder $query): Builder
    {
        return $query
            ->where('download_status', self::STATUS_DOWNLOADED)
            ->whereNotNull('storage_path')
            ->whereNotNull('preview_path');
    }

    public function scopeOrderedForGallery(Builder $query): Builder
    {
        return $query
            ->orderBy('source_created_at', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * @param  array<int, string>  $tags
     */
    public function scopeMatchingAllTags(Builder $query, array $tags): Builder
    {
        foreach ($tags as $tag) {
            $query->whereHas('tags', function (Builder $query) use ($tag): void {
                $query->where('name', $tag);
            });
        }

        return $query;
    }
}
