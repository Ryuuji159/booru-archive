<?php

use App\Actions\ProcessPendingScrapeRequestAction;
use App\Jobs\ProcessPendingScrapeRequest;
use App\Models\Post;
use App\Models\ScrapeRequest;
use App\Models\Tag;
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
        ->and(Post::query()->count())->toBe(1)
        ->and(Post::query()->where('source_post_id', 985)->exists())->toBeFalse()
        ->and(Post::query()->where('source_post_id', 401288)->exists())->toBeTrue();
});
