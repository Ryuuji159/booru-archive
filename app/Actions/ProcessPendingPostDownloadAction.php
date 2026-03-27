<?php

namespace App\Actions;

use App\Models\Post;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProcessPendingPostDownloadAction
{
    private const BATCH_SIZE = 100;

    private const STALE_DOWNLOADING_AFTER_MINUTES = 15;

    private const FULL_DIRECTORY = 'full';

    private const PREVIEW_DIRECTORY = 'preview';

    private const SQLITE_LOCK_RETRY_ATTEMPTS = 5;

    private const SQLITE_LOCK_RETRY_DELAY_MICROSECONDS = 200_000;

    public function handle(): int
    {
        $this->releaseStaleDownloads();

        $posts = $this->runWithDatabaseLockRetry(function (): Collection {
            return DB::transaction(function (): Collection {
                /** @var Collection<int, Post> $posts */
                $posts = Post::query()
                    ->pendingDownload()
                    ->orderByRaw(
                        'case when download_status = ? then 0 when download_status = ? then 1 else 2 end',
                        [Post::STATUS_PENDING, Post::STATUS_FAILED],
                    )
                    ->orderBy('id')
                    ->limit(self::BATCH_SIZE)
                    ->lockForUpdate()
                    ->get();

                $posts->each(function (Post $post): void {
                    $post->forceFill([
                        'download_status' => Post::STATUS_DOWNLOADING,
                        'download_attempts' => $post->download_attempts + 1,
                        'downloaded_at' => null,
                        'last_download_error' => null,
                    ])->save();
                });

                return $posts;
            });
        });

        foreach ($posts as $post) {
            $this->processClaimedPost($post);
        }

        return $posts->count();
    }

    private function releaseStaleDownloads(): void
    {
        $staleBefore = now()->subMinutes(self::STALE_DOWNLOADING_AFTER_MINUTES);

        $this->runWithDatabaseLockRetry(function () use ($staleBefore): void {
            Post::query()
                ->where('download_status', Post::STATUS_DOWNLOADING)
                ->where('updated_at', '<=', $staleBefore)
                ->update([
                    'download_status' => Post::STATUS_PENDING,
                    'last_download_error' => $this->staleDownloadMessage(),
                ]);
        });
    }

    private function processClaimedPost(Post $claimedPost): ?Post
    {
        $post = Post::query()->find($claimedPost->getKey());

        if (! $post || $post->download_status !== Post::STATUS_DOWNLOADING) {
            return null;
        }

        if ($this->shouldDeleteUndownloadablePost($post)) {
            $this->deletePost($post);

            return null;
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'post-download-');

        if ($temporaryFile === false) {
            $this->markAsFailed($post, 'Unable to create a temporary file for the download.');

            return $post->fresh();
        }

        $previewTemporaryFile = null;

        if (filled($post->source_preview_url)) {
            $previewTemporaryFile = tempnam(sys_get_temp_dir(), 'post-preview-download-');

            if ($previewTemporaryFile === false) {
                @unlink($temporaryFile);
                $this->markAsFailed($post, 'Unable to create a temporary file for the preview download.');

                return $post->fresh();
            }
        }

        try {
            $this->downloadToTemporaryFile(
                url: $post->source_file_url,
                temporaryFile: $temporaryFile,
                label: 'file',
            );

            $actualMd5 = hash_file('md5', $temporaryFile);

            if ($actualMd5 === false) {
                throw new RuntimeException('Unable to calculate the file hash for the downloaded post.');
            }

            if ($previewTemporaryFile) {
                $this->downloadToTemporaryFile(
                    url: $post->source_preview_url,
                    temporaryFile: $previewTemporaryFile,
                    label: 'preview',
                );
            }

            $fileExtension = $this->resolveFileExtension(
                fallbackExtension: $post->file_ext,
                url: $post->source_file_url,
            );
            $previewExtension = $this->resolveFileExtension(
                fallbackExtension: null,
                url: $post->source_preview_url,
            );
            $storageDisk = $this->resolveStorageDisk($post, $actualMd5);
            $storagePath = $this->resolveStoredPath(
                post: $post,
                actualMd5: $actualMd5,
                fileExtension: $fileExtension,
                field: 'storage_path',
                directory: self::FULL_DIRECTORY,
            );
            $previewPath = $this->resolvePreviewPath(
                post: $post,
                actualMd5: $actualMd5,
                previewExtension: $previewExtension,
            );

            $this->storeTemporaryFile(
                disk: $storageDisk,
                path: $storagePath,
                temporaryFile: $temporaryFile,
            );

            if ($previewPath && $previewTemporaryFile) {
                $this->storeTemporaryFile(
                    disk: $storageDisk,
                    path: $previewPath,
                    temporaryFile: $previewTemporaryFile,
                );
            }

            return $this->finalizeDownloadedPost(
                post: $post,
                actualMd5: $actualMd5,
                storageDisk: $storageDisk,
                storagePath: $storagePath,
                previewPath: $previewPath,
                fileExtension: $fileExtension,
                temporaryFile: $temporaryFile,
            );
        } catch (\Throwable $exception) {
            $this->markAsFailed($post, $exception->getMessage());

            return $post->fresh();
        } finally {
            @unlink($temporaryFile);

            if ($previewTemporaryFile) {
                @unlink($previewTemporaryFile);
            }
        }
    }

    private function downloadToTemporaryFile(string $url, string $temporaryFile, string $label): void
    {
        if (blank($url)) {
            throw new RuntimeException("The post has no source {$label} URL to download.");
        }

        $response = Http::connectTimeout(10)
            ->timeout(120)
            ->retry([250, 750, 1500], throw: false)
            ->withOptions([
                'sink' => $temporaryFile,
            ])
            ->get($url);

        if ($response->failed()) {
            throw new RuntimeException("The {$label} download failed with status [{$response->status()}].");
        }

        if (filesize($temporaryFile) === 0) {
            file_put_contents($temporaryFile, $response->body());
        }

        if (filesize($temporaryFile) === 0) {
            throw new RuntimeException("The downloaded {$label} is empty.");
        }
    }

    private function resolveStorageDisk(Post $post, string $actualMd5): string
    {
        $duplicate = Post::query()
            ->where('md5', $actualMd5)
            ->whereKeyNot($post->getKey())
            ->whereNotNull('storage_disk')
            ->orderBy('id')
            ->first();

        return $duplicate?->storage_disk ?: ($post->storage_disk ?: 'local');
    }

    private function resolveStoredPath(
        Post $post,
        string $actualMd5,
        ?string $fileExtension,
        string $field,
        string $directory,
    ): string {
        $duplicate = Post::query()
            ->where('md5', $actualMd5)
            ->whereKeyNot($post->getKey())
            ->whereNotNull($field)
            ->orderBy('id')
            ->first();

        return $duplicate?->{$field} ?: $this->buildStoragePath(
            md5: $actualMd5,
            directory: $directory,
            fileExtension: $fileExtension,
        );
    }

    private function resolvePreviewPath(Post $post, string $actualMd5, ?string $previewExtension): ?string
    {
        $duplicate = Post::query()
            ->where('md5', $actualMd5)
            ->whereKeyNot($post->getKey())
            ->whereNotNull('preview_path')
            ->orderBy('id')
            ->first();

        if ($duplicate?->preview_path) {
            return $duplicate->preview_path;
        }

        if (blank($post->source_preview_url)) {
            return null;
        }

        return $this->buildStoragePath(
            md5: $actualMd5,
            directory: self::PREVIEW_DIRECTORY,
            fileExtension: $previewExtension,
        );
    }

    private function buildStoragePath(string $md5, string $directory, ?string $fileExtension): string
    {
        $normalizedMd5 = strtolower($md5);
        $extensionSuffix = filled($fileExtension) ? '.'.strtolower($fileExtension) : '';

        return sprintf(
            '%s/%s/%s/%s/%s%s',
            $directory,
            substr($normalizedMd5, 0, 2),
            substr($normalizedMd5, 2, 2),
            substr($normalizedMd5, 4, 2),
            $normalizedMd5,
            $extensionSuffix,
        );
    }

    private function storeTemporaryFile(string $disk, string $path, string $temporaryFile): void
    {
        $storage = Storage::disk($disk);

        if ($storage->exists($path)) {
            return;
        }

        $stream = fopen($temporaryFile, 'r');

        if ($stream === false) {
            throw new RuntimeException('Unable to open the downloaded file for storage.');
        }

        try {
            $storage->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }
    }

    private function finalizeDownloadedPost(
        Post $post,
        string $actualMd5,
        string $storageDisk,
        string $storagePath,
        ?string $previewPath,
        ?string $fileExtension,
        string $temporaryFile,
    ): Post {
        return $this->runWithDatabaseLockRetry(function () use ($post, $actualMd5, $storageDisk, $storagePath, $previewPath, $fileExtension, $temporaryFile): Post {
            return DB::transaction(function () use ($post, $actualMd5, $storageDisk, $storagePath, $previewPath, $fileExtension, $temporaryFile): Post {
                /** @var Post $currentPost */
                $currentPost = Post::query()
                    ->with('tags:id')
                    ->lockForUpdate()
                    ->findOrFail($post->getKey());

                /** @var Collection<int, Post> $duplicates */
                $duplicates = Post::query()
                    ->with('tags:id')
                    ->where('md5', $actualMd5)
                    ->whereKeyNot($currentPost->getKey())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                /** @var Collection<int, Post> $candidates */
                $candidates = collect([$currentPost])->merge($duplicates)->sortBy('id')->values();
                /** @var Post $canonicalPost */
                $canonicalPost = $candidates->first();
                $duplicatePostIds = $candidates
                    ->slice(1)
                    ->map(fn (Post $candidate): int => $candidate->getKey())
                    ->values()
                    ->all();

                $tagIds = $candidates
                    ->flatMap(fn (Post $candidate): array => $candidate->tags->modelKeys())
                    ->unique()
                    ->values()
                    ->all();

                $canonicalPost->forceFill([
                    'md5' => $actualMd5,
                    'file_ext' => $fileExtension ?: $canonicalPost->file_ext ?: $currentPost->file_ext,
                    'file_size' => $this->temporaryFileSize($temporaryFile) ?: $canonicalPost->file_size ?: $currentPost->file_size,
                    'width' => $canonicalPost->width ?: $currentPost->width,
                    'height' => $canonicalPost->height ?: $currentPost->height,
                    'rating' => $canonicalPost->rating ?: $currentPost->rating,
                    'score' => $canonicalPost->score ?? $currentPost->score,
                    'author' => $canonicalPost->author ?: $currentPost->author,
                    'source_created_at' => $canonicalPost->source_created_at ?: $currentPost->source_created_at,
                    'source_file_url' => $canonicalPost->source_file_url ?: $currentPost->source_file_url,
                    'source_preview_url' => $canonicalPost->source_preview_url ?: $currentPost->source_preview_url,
                    'storage_disk' => $storageDisk,
                    'storage_path' => $storagePath,
                    'preview_path' => $previewPath ?: $canonicalPost->preview_path ?: $currentPost->preview_path,
                    'download_status' => Post::STATUS_DOWNLOADED,
                    'downloaded_at' => now(),
                    'last_download_error' => null,
                ])->save();

                if ($tagIds !== []) {
                    $canonicalPost->tags()->syncWithoutDetaching($tagIds);
                }

                if ($duplicatePostIds !== []) {
                    Post::query()->whereKey($duplicatePostIds)->delete();
                }

                return $canonicalPost->fresh(['tags']);
            });
        });
    }

    private function resolveFileExtension(?string $fallbackExtension, ?string $url): ?string
    {
        if (filled($fallbackExtension)) {
            return strtolower((string) $fallbackExtension);
        }

        if (blank($url)) {
            return null;
        }

        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);

        return filled($extension) ? strtolower((string) $extension) : null;
    }

    private function temporaryFileSize(string $temporaryFile): ?int
    {
        $size = filesize($temporaryFile);

        return $size === false ? null : $size;
    }

    private function markAsFailed(Post $post, string $message): void
    {
        $this->runWithDatabaseLockRetry(function () use ($post, $message): void {
            Post::query()
                ->whereKey($post->getKey())
                ->update([
                    'download_status' => Post::STATUS_FAILED,
                    'last_download_error' => $message,
                ]);
        });
    }

    private function shouldDeleteUndownloadablePost(Post $post): bool
    {
        return data_get($post->source_payload, 'status') === 'deleted'
            && blank($post->source_file_url);
    }

    private function deletePost(Post $post): void
    {
        $this->runWithDatabaseLockRetry(function () use ($post): void {
            Post::query()->whereKey($post->getKey())->delete();
        });
    }

    private function staleDownloadMessage(): string
    {
        return sprintf(
            'Download claim expired after %d minutes without updates and was returned to pending.',
            self::STALE_DOWNLOADING_AFTER_MINUTES,
        );
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function runWithDatabaseLockRetry(Closure $callback): mixed
    {
        $attempt = 1;

        beginning:

        try {
            return $callback();
        } catch (QueryException $exception) {
            if (
                $attempt >= self::SQLITE_LOCK_RETRY_ATTEMPTS
                || ! $this->isSqliteLockException($exception)
            ) {
                throw $exception;
            }

            usleep(self::SQLITE_LOCK_RETRY_DELAY_MICROSECONDS * $attempt);
            $attempt++;

            goto beginning;
        }
    }

    private function isSqliteLockException(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'database is locked')
            || str_contains($message, 'database table is locked')
            || str_contains($message, 'general error: 5');
    }
}
