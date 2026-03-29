<?php

use App\Actions\ProcessPendingScrapeRequestAction;
use App\Jobs\ProcessPendingScrapeRequest;
use App\Models\Post;
use App\Models\ScrapeRequest;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('processes the first pending scrape request and creates posts with tags', function () {
    Http::fake([
        'https://konachan.com/post.json*' => Http::response([
            [
                'id' => 401288,
                'tags' => 'ass blonde_hair blue_eyes walkure_romanze',
                'created_at' => 1774570395,
                'author' => 'S17',
                'score' => 6,
                'md5' => '07dfd9c5614fbccd40b39a9bd8c86808',
                'file_size' => 853047,
                'file_url' => 'https://konachan.com/image/07dfd9c5614fbccd40b39a9bd8c86808/post.jpg',
                'preview_url' => 'https://konachan.com/data/preview/07/df/07dfd9c5614fbccd40b39a9bd8c86808.jpg',
                'rating' => 'q',
                'status' => 'pending',
                'width' => 1920,
                'height' => 1080,
            ],
            [
                'id' => 401289,
                'tags' => 'blue_eyes dress long_hair walkure_romanze',
                'created_at' => 1774570396,
                'author' => 'S18',
                'score' => 9,
                'md5' => '17dfd9c5614fbccd40b39a9bd8c86809',
                'file_size' => 753047,
                'file_url' => 'https://konachan.com/image/17dfd9c5614fbccd40b39a9bd8c86809/post.png',
                'preview_url' => 'https://konachan.com/data/preview/17/df/17dfd9c5614fbccd40b39a9bd8c86809.jpg',
                'rating' => 's',
                'status' => 'active',
                'width' => 1200,
                'height' => 800,
            ],
        ], 200),
    ]);

    $scrapeRequest = ScrapeRequest::query()->create([
        'site' => 'konachan',
        'parameters' => [
            'tags' => ['rating:safe', 'walkure_romanze'],
        ],
        'status' => ScrapeRequest::STATUS_PENDING,
    ]);

    app(ProcessPendingScrapeRequest::class)->handle(app(ProcessPendingScrapeRequestAction::class));

    $scrapeRequest->refresh();

    expect($scrapeRequest->status)->toBe(ScrapeRequest::STATUS_COMPLETED)
        ->and($scrapeRequest->discovered_posts_count)->toBe(2)
        ->and($scrapeRequest->last_processed_page)->toBe(1)
        ->and($scrapeRequest->started_at)->not->toBeNull()
        ->and($scrapeRequest->finished_at)->not->toBeNull();

    expect(Post::query()->count())->toBe(2)
        ->and(Tag::query()->count())->toBe(6);

    $firstPost = Post::query()
        ->where('source_post_id', 401288)
        ->firstOrFail();

    expect($firstPost->file_ext)->toBe('jpg')
        ->and($firstPost->source_site)->toBe('konachan')
        ->and($firstPost->tags()->pluck('name')->all())
        ->toMatchArray(['ass', 'blonde_hair', 'blue_eyes', 'walkure_romanze']);

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request): bool {
        $userAgent = implode(' ', (array) $request->header('User-Agent'));

        return str_contains($request->url(), '/post.json')
            && str_contains($userAgent, config('app.name'))
            && str_contains($userAgent, config('app.url'))
            && $request['page'] === 1
            && $request['limit'] === 100
            && $request['tags'] === 'rating:safe walkure_romanze';
    });
});

it('marks the scrape request as failed when the source is unsupported', function () {
    $scrapeRequest = ScrapeRequest::query()->create([
        'site' => 'yandere',
        'parameters' => [
            'tags' => ['safe'],
        ],
        'status' => ScrapeRequest::STATUS_PENDING,
    ]);

    app(ProcessPendingScrapeRequest::class)->handle(app(ProcessPendingScrapeRequestAction::class));

    $scrapeRequest->refresh();

    expect($scrapeRequest->status)->toBe(ScrapeRequest::STATUS_FAILED)
        ->and($scrapeRequest->last_error)->toContain('Unsupported scrape source [yandere].');
});

it('skips deleted or undownloadable posts during scrape import', function () {
    Http::fake([
        'https://konachan.com/post.json*' => Http::response([
            [
                'id' => 985,
                'tags' => 'animal_ears duplicate lucky_star',
                'created_at' => 1200250765,
                'author' => 'Oyashiro-sama',
                'score' => 3,
                'md5' => '45e2a4f059686c9df236b228574455dc',
                'file_size' => 349462,
                'preview_url' => 'https://konachan.com/deleted-preview.png',
                'rating' => 's',
                'status' => 'deleted',
                'width' => 1600,
                'height' => 1200,
                'flag_detail' => [
                    'reason' => 'dupe',
                ],
            ],
            [
                'id' => 401288,
                'tags' => 'ass blonde_hair blue_eyes walkure_romanze',
                'created_at' => 1774570395,
                'author' => 'S17',
                'score' => 6,
                'md5' => '07dfd9c5614fbccd40b39a9bd8c86808',
                'file_size' => 853047,
                'file_url' => 'https://konachan.com/image/07dfd9c5614fbccd40b39a9bd8c86808/post.jpg',
                'preview_url' => 'https://konachan.com/data/preview/07/df/07dfd9c5614fbccd40b39a9bd8c86808.jpg',
                'rating' => 'q',
                'status' => 'active',
                'width' => 1920,
                'height' => 1080,
            ],
        ], 200),
    ]);

    $scrapeRequest = ScrapeRequest::query()->create([
        'site' => 'konachan',
        'parameters' => [
            'tags' => ['walkure_romanze'],
        ],
        'status' => ScrapeRequest::STATUS_PENDING,
    ]);

    app(ProcessPendingScrapeRequest::class)->handle(app(ProcessPendingScrapeRequestAction::class));

    $scrapeRequest->refresh();

    expect($scrapeRequest->status)->toBe(ScrapeRequest::STATUS_COMPLETED)
        ->and($scrapeRequest->discovered_posts_count)->toBe(1)
        ->and($scrapeRequest->last_processed_page)->toBe(1)
        ->and(Post::query()->count())->toBe(1)
        ->and(Post::query()->where('source_post_id', 985)->exists())->toBeFalse()
        ->and(Post::query()->where('source_post_id', 401288)->exists())->toBeTrue();
});

it('resumes a stale running scrape request from the last processed page', function () {
    Http::fake([
        'https://konachan.com/post.json*' => function (Request $request) {
            return Http::response([
                [
                    'id' => 401289,
                    'tags' => 'blue_eyes long_hair',
                    'created_at' => 1774570400,
                    'author' => 'S19',
                    'score' => 12,
                    'md5' => '27dfd9c5614fbccd40b39a9bd8c86810',
                    'file_size' => 553047,
                    'file_url' => 'https://konachan.com/image/27dfd9c5614fbccd40b39a9bd8c86810/post.jpg',
                    'preview_url' => 'https://konachan.com/data/preview/27/df/27dfd9c5614fbccd40b39a9bd8c86810.jpg',
                    'rating' => 's',
                    'status' => 'active',
                    'width' => 1600,
                    'height' => 900,
                ],
            ], 200);
        },
    ]);

    $scrapeRequest = ScrapeRequest::query()->create([
        'site' => 'konachan',
        'parameters' => [
            'tags' => ['walkure_romanze'],
        ],
        'status' => ScrapeRequest::STATUS_RUNNING,
        'discovered_posts_count' => 200,
        'last_processed_page' => 2,
        'started_at' => CarbonImmutable::now()->subMinutes(20),
        'finished_at' => null,
        'last_error' => 'timeout',
    ]);
    $scrapeRequest->forceFill([
        'updated_at' => CarbonImmutable::now()->subMinutes(20),
    ])->saveQuietly();

    Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401289,
        'md5' => '27dfd9c5614fbccd40b39a9bd8c86810',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/27dfd9c5614fbccd40b39a9bd8c86810/post.jpg',
        'source_preview_url' => 'https://konachan.com/data/preview/27/df/27dfd9c5614fbccd40b39a9bd8c86810.jpg',
        'download_status' => Post::STATUS_PENDING,
    ]);

    app(ProcessPendingScrapeRequest::class)->handle(app(ProcessPendingScrapeRequestAction::class));

    $scrapeRequest->refresh();

    expect($scrapeRequest->status)->toBe(ScrapeRequest::STATUS_COMPLETED)
        ->and($scrapeRequest->discovered_posts_count)->toBe(200)
        ->and($scrapeRequest->last_processed_page)->toBe(2)
        ->and($scrapeRequest->last_error)->toBeNull()
        ->and(Post::query()->where('source_post_id', 401289)->exists())->toBeTrue();

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), '/post.json')
            && $request['page'] === 2
            && $request['limit'] === 100;
    });
});

it('does not expire a recently updated running scrape request', function () {
    Http::fake();

    $scrapeRequest = ScrapeRequest::query()->create([
        'site' => 'konachan',
        'parameters' => [
            'tags' => ['walkure_romanze'],
        ],
        'status' => ScrapeRequest::STATUS_RUNNING,
        'discovered_posts_count' => 12,
        'last_processed_page' => 4,
        'started_at' => CarbonImmutable::now()->subMinutes(5),
        'finished_at' => null,
        'last_error' => null,
    ]);
    $scrapeRequest->forceFill([
        'updated_at' => CarbonImmutable::now()->subMinutes(5),
    ])->saveQuietly();

    app(ProcessPendingScrapeRequest::class)->handle(app(ProcessPendingScrapeRequestAction::class));

    $scrapeRequest->refresh();

    expect($scrapeRequest->status)->toBe(ScrapeRequest::STATUS_RUNNING)
        ->and($scrapeRequest->discovered_posts_count)->toBe(12)
        ->and($scrapeRequest->last_processed_page)->toBe(4);

    Http::assertNothingSent();
});

it('processes at most ten pages per scrape run and leaves the request pending when more pages remain', function () {
    Http::fake([
        'https://konachan.com/post.json*' => function (Request $request) {
            $page = (int) $request['page'];
            $items = [];

            for ($index = 1; $index <= 100; $index++) {
                $postId = ($page * 1000) + $index;

                $items[] = [
                    'id' => $postId,
                    'tags' => '',
                    'created_at' => 1774570400 + $postId,
                    'author' => 'S'.$postId,
                    'score' => $page,
                    'md5' => str_pad(dechex($postId), 32, '0', STR_PAD_LEFT),
                    'file_size' => 1000 + $index,
                    'file_url' => "https://konachan.com/image/{$postId}.jpg",
                    'preview_url' => "https://konachan.com/data/preview/{$postId}.jpg",
                    'rating' => 's',
                    'status' => 'active',
                    'width' => 1000,
                    'height' => 1000,
                ];
            }

            return Http::response($items, 200);
        },
    ]);

    $scrapeRequest = ScrapeRequest::query()->create([
        'site' => 'konachan',
        'parameters' => [
            'tags' => ['walkure_romanze'],
        ],
        'status' => ScrapeRequest::STATUS_PENDING,
    ]);

    app(ProcessPendingScrapeRequest::class)->handle(app(ProcessPendingScrapeRequestAction::class));

    $scrapeRequest->refresh();

    expect($scrapeRequest->status)->toBe(ScrapeRequest::STATUS_PENDING)
        ->and($scrapeRequest->discovered_posts_count)->toBe(1000)
        ->and($scrapeRequest->last_processed_page)->toBe(10)
        ->and($scrapeRequest->finished_at)->toBeNull()
        ->and(Post::query()->count())->toBe(1000);

    Http::assertSentCount(10);
    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), '/post.json')
            && $request['page'] === 10
            && $request['limit'] === 100;
    });
});
