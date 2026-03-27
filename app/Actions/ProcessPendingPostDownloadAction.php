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

    private const SQLITE_LOCK_RETRY_ATTEMPTS = 5;

    private const SQLITE_LOCK_RETRY_DELAY_MICROSECONDS = 200_000;

    public function handle(): int
    {
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

    private function processClaimedPost(Post $claimedPost): ?Post
    {
        $post = Post::query()->find($claimedPost->getKey());

        if (! $post || $post->download_status !== Post::STATUS_DOWNLOADING) {
            return null;
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'post-download-');

        if ($temporaryFile === false) {
            $this->markAsFailed($post, 'Unable to create a temporary file for the download.');

            return $post->fresh();
        }

        try {
            $this->downloadToTemporaryFile($post, $temporaryFile);

            $actualMd5 = hash_file('md5', $temporaryFile);

            if ($actualMd5 === false) {
                throw new RuntimeException('Unable to calculate the file hash for the downloaded post.');
            }

            $fileExtension = $this->resolveFileExtension($post);
            $storageDisk = $this->resolveStorageDisk($post, $actualMd5);
            $storagePath = $this->resolveStoragePath($post, $actualMd5, $fileExtension);

            $this->storeTemporaryFile(
                disk: $storageDisk,
                path: $storagePath,
                temporaryFile: $temporaryFile,
            );

            return $this->finalizeDownloadedPost(
                post: $post,
                actualMd5: $actualMd5,
                storageDisk: $storageDisk,
                storagePath: $storagePath,
                fileExtension: $fileExtension,
                temporaryFile: $temporaryFile,
            );
        } catch (\Throwable $exception) {
            $this->markAsFailed($post, $exception->getMessage());

            return $post->fresh();
        } finally {
            @unlink($temporaryFile);
        }
    }

    private function downloadToTemporaryFile(Post $post, string $temporaryFile): void
    {
        if (blank($post->source_file_url)) {
            throw new RuntimeException('The post has no source file URL to download.');
        }

        $response = Http::connectTimeout(10)
            ->timeout(120)
            ->retry([250, 750, 1500], throw: false)
            ->withOptions([
                'sink' => $temporaryFile,
            ])
            ->get($post->source_file_url);

        if ($response->failed()) {
            throw new RuntimeException("The file download failed with status [{$response->status()}].");
        }

        if (filesize($temporaryFile) === 0) {
            file_put_contents($temporaryFile, $response->body());
        }

        if (filesize($temporaryFile) === 0) {
            throw new RuntimeException('The downloaded file is empty.');
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

    private function resolveStoragePath(Post $post, string $actualMd5, ?string $fileExtension): string
    {
        $duplicate = Post::query()
            ->where('md5', $actualMd5)
            ->whereKeyNot($post->getKey())
            ->whereNotNull('storage_path')
            ->orderBy('id')
            ->first();

        return $duplicate?->storage_path ?: $this->buildStoragePath($actualMd5, $fileExtension);
    }

    private function buildStoragePath(string $md5, ?string $fileExtension): string
    {
        $normalizedMd5 = strtolower($md5);
        $extensionSuffix = filled($fileExtension) ? '.'.strtolower($fileExtension) : '';

        return sprintf(
            '%s/%s/%s/%s%s',
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
        ?string $fileExtension,
        string $temporaryFile,
    ): Post {
        return $this->runWithDatabaseLockRetry(function () use ($post, $actualMd5, $storageDisk, $storagePath, $fileExtension, $temporaryFile): Post {
            return DB::transaction(function () use ($post, $actualMd5, $storageDisk, $storagePath, $fileExtension, $temporaryFile): Post {
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

    private function resolveFileExtension(Post $post): ?string
    {
        if (filled($post->file_ext)) {
            return strtolower((string) $post->file_ext);
        }

        $extension = pathinfo(parse_url($post->source_file_url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);

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
