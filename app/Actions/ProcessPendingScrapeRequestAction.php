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

    private const STALE_RUNNING_AFTER_MINUTES = 15;

    /**
     * @var array<string, int>
     */
    private array $tagIdsByName = [];

    public function __construct(
        private readonly KonachanService $konachanService,
    ) {}

    public function handle(): ?ScrapeRequest
    {
        $this->releaseStaleRunningRequests();

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
                'started_at' => $scrapeRequest->started_at ?? now(),
                'finished_at' => null,
                'last_error' => null,
            ])->save();

            return $scrapeRequest;
        });

        if (! $scrapeRequest) {
            return null;
        }

        try {
            [$discoveredPostsCount, $completed] = match ($scrapeRequest->site) {
                'konachan' => $this->processKonachanRequest($scrapeRequest),
                default => throw new RuntimeException("Unsupported scrape source [{$scrapeRequest->site}]."),
            };

            $scrapeRequest->forceFill([
                'status' => $completed ? ScrapeRequest::STATUS_COMPLETED : ScrapeRequest::STATUS_PENDING,
                'discovered_posts_count' => $discoveredPostsCount,
                'finished_at' => $completed ? now() : null,
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

    /**
     * @return array{0: int, 1: bool}
     */
    private function processKonachanRequest(ScrapeRequest $scrapeRequest): array
    {
        $tags = collect($scrapeRequest->parameters['tags'] ?? [])
            ->filter(fn (mixed $tag): bool => filled($tag))
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->values()
            ->all();

        if ($tags === []) {
            throw new RuntimeException('Search request has no tags to process.');
        }

        $discoveredPostsCount = (int) $scrapeRequest->discovered_posts_count;
        $page = $this->startingPage($scrapeRequest);
        $pagesProcessed = 0;
        $maxPagesPerRun = $this->pagesPerRun();
        $completed = false;

        do {
            $pagePosts = $this->konachanService->posts(
                tags: $tags,
                limit: self::PAGE_SIZE,
                page: $page,
            );

            foreach ($pagePosts as $postPayload) {
                $wasStored = $this->storePostFromPayload(
                    sourceSite: $scrapeRequest->site,
                    payload: $postPayload,
                );

                if ($wasStored) {
                    $discoveredPostsCount++;
                }
            }

            $scrapeRequest->forceFill([
                'discovered_posts_count' => $discoveredPostsCount,
                'last_processed_page' => $page,
            ])->save();
            $page++;
            $pagesProcessed++;
            $completed = count($pagePosts) < self::PAGE_SIZE;
        } while (! $completed && $pagesProcessed < $maxPagesPerRun);

        return [$discoveredPostsCount, $completed];
    }

    private function releaseStaleRunningRequests(): void
    {
        $staleBefore = now()->subMinutes(self::STALE_RUNNING_AFTER_MINUTES);

        ScrapeRequest::query()
            ->where('status', ScrapeRequest::STATUS_RUNNING)
            ->where('updated_at', '<=', $staleBefore)
            ->update([
                'status' => ScrapeRequest::STATUS_PENDING,
                'last_error' => $this->staleRunningMessage(),
            ]);
    }

    private function startingPage(ScrapeRequest $scrapeRequest): int
    {
        return max(1, (int) ($scrapeRequest->last_processed_page ?: 1));
    }

    private function pagesPerRun(): int
    {
        return max(1, (int) config('services.konachan.scrape_pages_per_run', 30));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storePostFromPayload(string $sourceSite, array $payload): bool
    {
        if (! $this->shouldImportPayload($payload)) {
            return false;
        }

        $sourceFileUrl = $this->resolveSourceFileUrl($payload);

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
                'source_file_url' => $sourceFileUrl,
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

        return $post->wasRecentlyCreated;
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
        $fileUrl = $this->resolveSourceFileUrl($payload);

        if (! filled($fileUrl)) {
            return null;
        }

        $path = parse_url((string) $fileUrl, PHP_URL_PATH);
        $extension = pathinfo((string) $path, PATHINFO_EXTENSION);

        return filled($extension) ? strtolower($extension) : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function shouldImportPayload(array $payload): bool
    {
        if (($payload['status'] ?? null) === 'deleted') {
            return false;
        }

        return filled($this->resolveSourceFileUrl($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveSourceFileUrl(array $payload): string
    {
        return (string) ($payload['file_url'] ?? $payload['sample_url'] ?? $payload['jpeg_url'] ?? '');
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableTimestamp(mixed $value): ?Carbon
    {
        return is_numeric($value) ? Carbon::createFromTimestamp((int) $value) : null;
    }

    private function staleRunningMessage(): string
    {
        return sprintf(
            'Scrape claim expired after %d minutes without updates and was returned to pending.',
            self::STALE_RUNNING_AFTER_MINUTES,
        );
    }
}
