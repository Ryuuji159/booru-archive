<?php

namespace App\Actions;

use App\Models\Post;
use App\Models\ScrapeRequest;
use App\Models\Tag;
use App\Services\KonachanService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessPendingScrapeRequestAction
{
    private const PAGE_SIZE = 100;

    /**
     * @var array<string, int>
     */
    private array $tagIdsByName = [];

    public function __construct(
        private readonly KonachanService $konachanService,
    ) {}

    public function handle(): ?ScrapeRequest
    {
        $scrapeRequest = DB::transaction(function (): ?ScrapeRequest {
            /** @var ?ScrapeRequest $scrapeRequest */
            $scrapeRequest = ScrapeRequest::query()
                ->pending()
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $scrapeRequest) {
                return null;
            }

            $scrapeRequest->forceFill([
                'status' => ScrapeRequest::STATUS_RUNNING,
                'started_at' => now(),
                'finished_at' => null,
                'last_error' => null,
                'discovered_posts_count' => 0,
            ])->save();

            return $scrapeRequest;
        });

        if (! $scrapeRequest) {
            return null;
        }

        try {
            $discoveredPostsCount = match ($scrapeRequest->site) {
                'konachan' => $this->processKonachanRequest($scrapeRequest),
                default => throw new RuntimeException("Unsupported scrape source [{$scrapeRequest->site}]."),
            };

            $scrapeRequest->forceFill([
                'status' => ScrapeRequest::STATUS_COMPLETED,
                'discovered_posts_count' => $discoveredPostsCount,
                'finished_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (\Throwable $exception) {
            $scrapeRequest->forceFill([
                'status' => ScrapeRequest::STATUS_FAILED,
                'finished_at' => now(),
                'last_error' => $exception->getMessage(),
            ])->save();
        }

        return $scrapeRequest->fresh();
    }

    private function processKonachanRequest(ScrapeRequest $scrapeRequest): int
    {
        $tags = collect($scrapeRequest->parameters['tags'] ?? [])
            ->filter(fn (mixed $tag): bool => filled($tag))
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->values()
            ->all();

        if ($tags === []) {
            throw new RuntimeException('Search request has no tags to process.');
        }

        $discoveredPostsCount = 0;
        $page = 1;

        do {
            $pagePosts = $this->konachanService->posts(
                tags: $tags,
                limit: self::PAGE_SIZE,
                page: $page,
            );

            foreach ($pagePosts as $postPayload) {
                $this->storePostFromPayload(
                    sourceSite: $scrapeRequest->site,
                    payload: $postPayload,
                );
            }

            $discoveredPostsCount += count($pagePosts);
            $scrapeRequest->forceFill([
                'discovered_posts_count' => $discoveredPostsCount,
            ])->save();
            $page++;
        } while (count($pagePosts) === self::PAGE_SIZE);

        return $discoveredPostsCount;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storePostFromPayload(string $sourceSite, array $payload): void
    {
        $post = Post::query()->updateOrCreate(
            [
                'source_site' => $sourceSite,
                'source_post_id' => (int) ($payload['id'] ?? 0),
            ],
            [
                'md5' => (string) ($payload['md5'] ?? ''),
                'file_ext' => $this->inferFileExtension($payload),
                'file_size' => $this->nullableInteger($payload['file_size'] ?? null),
                'width' => $this->nullableInteger($payload['width'] ?? null),
                'height' => $this->nullableInteger($payload['height'] ?? null),
                'rating' => filled($payload['rating'] ?? null) ? (string) $payload['rating'] : null,
                'score' => $this->nullableInteger($payload['score'] ?? null),
                'author' => filled($payload['author'] ?? null) ? (string) $payload['author'] : null,
                'source_created_at' => $this->nullableTimestamp($payload['created_at'] ?? null),
                'source_file_url' => (string) ($payload['file_url'] ?? $payload['sample_url'] ?? $payload['jpeg_url'] ?? ''),
                'source_preview_url' => filled($payload['preview_url'] ?? null) ? (string) $payload['preview_url'] : null,
                'source_payload' => $payload,
            ],
        );

        $tagIds = collect(preg_split('/\s+/', trim((string) ($payload['tags'] ?? ''))) ?: [])
            ->filter(fn (string $tag): bool => $tag !== '')
            ->map(fn (string $tag): int => $this->getTagId($tag))
            ->unique()
            ->values()
            ->all();

        if ($tagIds !== []) {
            $post->tags()->syncWithoutDetaching($tagIds);
        }
    }

    private function getTagId(string $tagName): int
    {
        if (isset($this->tagIdsByName[$tagName])) {
            return $this->tagIdsByName[$tagName];
        }

        $tag = Tag::query()->firstOrCreate([
            'name' => $tagName,
        ]);

        return $this->tagIdsByName[$tagName] = $tag->getKey();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function inferFileExtension(array $payload): ?string
    {
        $fileUrl = $payload['file_url'] ?? $payload['sample_url'] ?? $payload['jpeg_url'] ?? null;

        if (! filled($fileUrl)) {
            return null;
        }

        $path = parse_url((string) $fileUrl, PHP_URL_PATH);
        $extension = pathinfo((string) $path, PATHINFO_EXTENSION);

        return filled($extension) ? strtolower($extension) : null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableTimestamp(mixed $value): ?Carbon
    {
        return is_numeric($value) ? Carbon::createFromTimestamp((int) $value) : null;
    }
}
