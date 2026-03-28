<?php

use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('serves private preview and full files through media routes', function () {
    Storage::fake('media');

    $previewPath = 'preview/aa/bb/cc/example.jpg';
    $fullPath = 'full/aa/bb/cc/example.jpg';

    Storage::disk('media')->put($previewPath, 'preview-bytes');
    Storage::disk('media')->put($fullPath, 'full-bytes');

    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1000,
        'md5' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1000.jpg',
        'storage_disk' => 'media',
        'storage_path' => $fullPath,
        'preview_path' => $previewPath,
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $previewUrl = route('posts.media.preview', $post);

    expect($previewUrl)->toContain('/media/posts/preview/');
    expect($previewUrl)->toContain($post->md5);

    $previewResponse = $this->get($previewUrl)
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=86400, public');

    expect($previewResponse->baseResponse)->toBeInstanceOf(BinaryFileResponse::class)
        ->and($previewResponse->baseResponse->getFile()->getPathname())->toBe(Storage::disk('media')->path($previewPath));

    $fullUrl = route('posts.media.full', $post);

    expect($fullUrl)->toContain('/media/posts/full/');
    expect($fullUrl)->toContain($post->md5);

    $fullResponse = $this->get($fullUrl)
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=86400, public');

    expect($fullResponse->baseResponse)->toBeInstanceOf(BinaryFileResponse::class)
        ->and($fullResponse->baseResponse->getFile()->getPathname())->toBe(Storage::disk('media')->path($fullPath));
});

it('returns 404 for media routes when the post is not downloadable', function () {
    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1001,
        'md5' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1001.jpg',
        'download_status' => Post::STATUS_FAILED,
    ]);

    $this->get(route('posts.media.preview', $post))->assertNotFound();
    $this->get(route('posts.media.full', $post))->assertNotFound();
});
