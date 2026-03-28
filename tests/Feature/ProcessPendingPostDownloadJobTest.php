<?php

use App\Actions\ProcessPendingPostDownloadAction;
use App\Jobs\ProcessPendingPostDownload;
use App\Models\Post;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('downloads the first pending post to the media disk using a hash-based path', function () {
    Storage::fake('media');

    $fileContents = 'post-image-binary';
    $previewContents = 'post-preview-binary';
    $md5 = md5($fileContents);

    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401288,
        'md5' => $md5,
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401288.jpg',
        'source_preview_url' => 'https://konachan.com/data/preview/post-401288.jpg',
        'download_status' => Post::STATUS_PENDING,
    ]);

    Http::fake([
        'https://konachan.com/image/post-401288.jpg' => Http::response($fileContents, 200),
        'https://konachan.com/data/preview/post-401288.jpg' => Http::response($previewContents, 200),
    ]);

    app(ProcessPendingPostDownload::class)->handle(app(ProcessPendingPostDownloadAction::class));

    $post->refresh();
    $expectedPath = sprintf(
        'full/%s/%s/%s/%s.jpg',
        substr($md5, 0, 2),
        substr($md5, 2, 2),
        substr($md5, 4, 2),
        $md5,
    );
    $expectedPreviewPath = sprintf(
        'preview/%s/%s/%s/%s.jpg',
        substr($md5, 0, 2),
        substr($md5, 2, 2),
        substr($md5, 4, 2),
        $md5,
    );

    expect($post->download_status)->toBe(Post::STATUS_DOWNLOADED)
        ->and($post->storage_disk)->toBe('media')
        ->and($post->storage_path)->toBe($expectedPath)
        ->and($post->preview_path)->toBe($expectedPreviewPath)
        ->and($post->downloaded_at)->not->toBeNull()
        ->and($post->file_size)->toBe(strlen($fileContents));

    Storage::disk('media')->assertExists($expectedPath);
    Storage::disk('media')->assertExists($expectedPreviewPath);

    Http::assertSent(function (Request $request): bool {
        $userAgent = implode(' ', (array) $request->header('User-Agent'));

        return $request->url() === 'https://konachan.com/image/post-401288.jpg'
            && str_contains($userAgent, config('app.name'))
            && str_contains($userAgent, config('app.url'));
    });

    Http::assertSent(function (Request $request): bool {
        $userAgent = implode(' ', (array) $request->header('User-Agent'));

        return $request->url() === 'https://konachan.com/data/preview/post-401288.jpg'
            && str_contains($userAgent, config('app.name'))
            && str_contains($userAgent, config('app.url'));
    });
});

it('processes multiple pending posts in a single batch and keeps going after a failure', function () {
    Storage::fake('media');

    $firstContents = 'first-post-image';
    $thirdContents = 'third-post-image';

    $firstPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401288,
        'md5' => md5($firstContents),
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401288.jpg',
        'source_preview_url' => 'https://konachan.com/data/preview/post-401288.jpg',
        'download_status' => Post::STATUS_PENDING,
    ]);

    $secondPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401289,
        'md5' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401289.jpg',
        'source_preview_url' => 'https://konachan.com/data/preview/post-401289.jpg',
        'download_status' => Post::STATUS_PENDING,
    ]);

    $thirdPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401290,
        'md5' => md5($thirdContents),
        'file_ext' => 'png',
        'source_file_url' => 'https://konachan.com/image/post-401290.png',
        'source_preview_url' => 'https://konachan.com/data/preview/post-401290.jpg',
        'download_status' => Post::STATUS_PENDING,
    ]);

    Http::fake([
        'https://konachan.com/image/post-401288.jpg' => Http::response($firstContents, 200),
        'https://konachan.com/data/preview/post-401288.jpg' => Http::response('first-preview', 200),
        'https://konachan.com/image/post-401289.jpg' => Http::response('missing', 404),
        'https://konachan.com/image/post-401290.png' => Http::response($thirdContents, 200),
        'https://konachan.com/data/preview/post-401290.jpg' => Http::response('third-preview', 200),
    ]);

    app(ProcessPendingPostDownload::class)->handle(app(ProcessPendingPostDownloadAction::class));

    $firstPost->refresh();
    $secondPost->refresh();
    $thirdPost->refresh();

    expect($firstPost->download_status)->toBe(Post::STATUS_DOWNLOADED)
        ->and($firstPost->preview_path)->not->toBeNull()
        ->and($secondPost->download_status)->toBe(Post::STATUS_FAILED)
        ->and($secondPost->last_download_error)->toContain('status [404]')
        ->and($thirdPost->download_status)->toBe(Post::STATUS_DOWNLOADED)
        ->and($thirdPost->preview_path)->not->toBeNull();

    Http::assertSent(function (Request $request): bool {
        $userAgent = implode(' ', (array) $request->header('User-Agent'));

        return $request->url() === 'https://konachan.com/image/post-401288.jpg'
            && str_contains($userAgent, config('app.name'))
            && str_contains($userAgent, config('app.url'));
    });

    Http::assertSent(function (Request $request): bool {
        $userAgent = implode(' ', (array) $request->header('User-Agent'));

        return $request->url() === 'https://konachan.com/image/post-401289.jpg'
            && str_contains($userAgent, config('app.name'))
            && str_contains($userAgent, config('app.url'));
    });

    Http::assertSent(function (Request $request): bool {
        $userAgent = implode(' ', (array) $request->header('User-Agent'));

        return $request->url() === 'https://konachan.com/image/post-401290.png'
            && str_contains($userAgent, config('app.name'))
            && str_contains($userAgent, config('app.url'));
    });
});

it('retries failed posts on later runs while they are still under the attempt limit', function () {
    Storage::fake('media');

    $fileContents = 'retryable-post-image';
    $md5 = md5($fileContents);

    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401291,
        'md5' => $md5,
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401291.jpg',
        'source_preview_url' => 'https://konachan.com/data/preview/post-401291.jpg',
        'download_status' => Post::STATUS_FAILED,
        'download_attempts' => 1,
        'last_download_error' => 'database is locked',
    ]);

    Http::fake([
        'https://konachan.com/image/post-401291.jpg' => Http::response($fileContents, 200),
        'https://konachan.com/data/preview/post-401291.jpg' => Http::response('preview', 200),
    ]);

    app(ProcessPendingPostDownload::class)->handle(app(ProcessPendingPostDownloadAction::class));

    $post->refresh();

    expect($post->download_status)->toBe(Post::STATUS_DOWNLOADED)
        ->and($post->download_attempts)->toBe(2)
        ->and($post->downloaded_at)->not->toBeNull()
        ->and($post->preview_path)->not->toBeNull()
        ->and($post->last_download_error)->toBeNull();
});

it('returns stale downloading posts to pending and retries them on the next pass', function () {
    Storage::fake('media');

    $fileContents = 'stale-downloading-post-image';
    $md5 = md5($fileContents);

    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401295,
        'md5' => $md5,
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401295.jpg',
        'source_preview_url' => 'https://konachan.com/data/preview/post-401295.jpg',
        'download_status' => Post::STATUS_DOWNLOADING,
        'download_attempts' => 1,
    ]);
    $post->forceFill([
        'updated_at' => CarbonImmutable::now()->subMinutes(20),
    ])->saveQuietly();

    Http::fake([
        'https://konachan.com/image/post-401295.jpg' => Http::response($fileContents, 200),
        'https://konachan.com/data/preview/post-401295.jpg' => Http::response('preview', 200),
    ]);

    app(ProcessPendingPostDownload::class)->handle(app(ProcessPendingPostDownloadAction::class));

    $post->refresh();

    expect($post->download_status)->toBe(Post::STATUS_DOWNLOADED)
        ->and($post->download_attempts)->toBe(2)
        ->and($post->downloaded_at)->not->toBeNull()
        ->and($post->last_download_error)->toBeNull();
});

it('does not recycle downloading posts that were updated recently', function () {
    Storage::fake('media');

    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401296,
        'md5' => 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401296.jpg',
        'download_status' => Post::STATUS_DOWNLOADING,
        'download_attempts' => 1,
    ]);
    $post->forceFill([
        'updated_at' => CarbonImmutable::now()->subMinutes(5),
    ])->saveQuietly();

    Http::fake();

    app(ProcessPendingPostDownload::class)->handle(app(ProcessPendingPostDownloadAction::class));

    $post->refresh();

    expect($post->download_status)->toBe(Post::STATUS_DOWNLOADING)
        ->and($post->download_attempts)->toBe(1);

    Http::assertNothingSent();
});

it('does not retry failed posts that already exhausted the attempt limit', function () {
    Storage::fake('media');

    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401292,
        'md5' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401292.jpg',
        'download_status' => Post::STATUS_FAILED,
        'download_attempts' => Post::MAX_DOWNLOAD_ATTEMPTS,
        'last_download_error' => 'database is locked',
    ]);

    Http::fake();

    app(ProcessPendingPostDownload::class)->handle(app(ProcessPendingPostDownloadAction::class));

    $post->refresh();

    expect($post->download_status)->toBe(Post::STATUS_FAILED)
        ->and($post->download_attempts)->toBe(Post::MAX_DOWNLOAD_ATTEMPTS);

    Http::assertNothingSent();
});

it('deletes deleted posts without a downloadable source before trying to download them', function () {
    Storage::fake('media');

    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 985,
        'md5' => '45e2a4f059686c9df236b228574455dc',
        'file_ext' => 'jpg',
        'source_file_url' => '',
        'source_preview_url' => 'https://konachan.com/deleted-preview.png',
        'download_status' => Post::STATUS_PENDING,
        'source_payload' => [
            'status' => 'deleted',
            'flag_detail' => [
                'reason' => 'dupe',
            ],
        ],
    ]);

    Http::fake();

    app(ProcessPendingPostDownload::class)->handle(app(ProcessPendingPostDownloadAction::class));

    expect(Post::query()->whereKey($post->getKey())->exists())->toBeFalse();

    Http::assertNothingSent();
});

it('merges tags into the canonical post and deletes the duplicate when the downloaded hash already exists', function () {
    Storage::fake('media');

    $fileContents = 'duplicate-post-image';
    $md5 = md5($fileContents);

    $canonicalPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401200,
        'md5' => $md5,
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401200.jpg',
        'download_status' => Post::STATUS_FAILED,
    ]);

    $duplicatePost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 401288,
        'md5' => 'ffffffffffffffffffffffffffffffff',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-401288.jpg',
        'download_status' => Post::STATUS_PENDING,
    ]);

    $canonicalTag = Tag::query()->create(['name' => 'existing_tag']);
    $duplicateTag = Tag::query()->create(['name' => 'new_tag']);

    $canonicalPost->tags()->attach($canonicalTag);
    $duplicatePost->tags()->attach($duplicateTag);

    Http::fake([
        'https://konachan.com/image/post-401288.jpg' => Http::response($fileContents, 200),
    ]);

    app(ProcessPendingPostDownload::class)->handle(app(ProcessPendingPostDownloadAction::class));

    expect(Post::query()->count())->toBe(1);

    $canonicalPost->refresh();

    expect($canonicalPost->download_status)->toBe(Post::STATUS_DOWNLOADED)
        ->and($canonicalPost->storage_disk)->toBe('media')
        ->and($canonicalPost->downloaded_at)->not->toBeNull()
        ->and($canonicalPost->tags()->pluck('name')->all())
        ->toMatchArray(['existing_tag', 'new_tag']);

    expect(Post::query()->whereKey($duplicatePost->getKey())->exists())->toBeFalse();
});
