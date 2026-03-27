<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
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

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
