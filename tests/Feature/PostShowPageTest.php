<?php

use App\Models\Post;
use App\Models\Tag;

it('shows a public post page with the full image and relevant metadata', function () {
    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1000,
        'md5' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'file_ext' => 'jpg',
        'file_size' => 274278,
        'author' => 'Oyashiro-sama',
        'rating' => 's',
        'width' => 1152,
        'height' => 864,
        'source_file_url' => 'https://konachan.com/image/post-1000.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/aa/aa/aa/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg',
        'preview_path' => 'preview/aa/aa/aa/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
        'source_created_at' => now(),
    ]);

    $post->tags()->attach([
        Tag::query()->create(['name' => 'touhou'])->id,
        Tag::query()->create(['name' => 'fairy'])->id,
    ]);

    $response = $this->get(route('posts.show', $post));

    $response->assertOk();
    $response->assertSee(route('posts.media.full', $post), false);
    $response->assertSee('konachan #1000', false);
    $response->assertSee('Oyashiro-sama', false);
    $response->assertSee('1152 x 864', false);
    $response->assertSee('touhou', false);
    $response->assertSee('fairy', false);
});

it('returns 404 for posts that are not ready for the public show page', function () {
    $post = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1001,
        'md5' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1001.jpg',
        'download_status' => Post::STATUS_FAILED,
    ]);

    $this->get(route('posts.show', $post))->assertNotFound();
});

it('shows previous and next navigation scoped to the current tag filter', function () {
    $firstPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1000,
        'md5' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1000.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/aa/aa/aa/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg',
        'preview_path' => 'preview/aa/aa/aa/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $secondPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1001,
        'md5' => 'abababababababababababababababab',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1001a.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/ab/ab/ab/abababababababababababababababab.jpg',
        'preview_path' => 'preview/ab/ab/ab/abababababababababababababababab.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $currentPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1002,
        'md5' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1002.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/bb/bb/bb/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb.jpg',
        'preview_path' => 'preview/bb/bb/bb/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $fourthPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1003,
        'md5' => 'cbcbcbcbcbcbcbcbcbcbcbcbcbcbcbcb',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1003a.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/cb/cb/cb/cbcbcbcbcbcbcbcbcbcbcbcbcbcbcbcb.jpg',
        'preview_path' => 'preview/cb/cb/cb/cbcbcbcbcbcbcbcbcbcbcbcbcbcbcbcb.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $lastPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1004,
        'md5' => 'cccccccccccccccccccccccccccccccc',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1004.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/cc/cc/cc/cccccccccccccccccccccccccccccccc.jpg',
        'preview_path' => 'preview/cc/cc/cc/cccccccccccccccccccccccccccccccc.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $otherPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1005,
        'md5' => 'dddddddddddddddddddddddddddddddd',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1005.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/dd/dd/dd/dddddddddddddddddddddddddddddddd.jpg',
        'preview_path' => 'preview/dd/dd/dd/dddddddddddddddddddddddddddddddd.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $touhou = Tag::query()->create(['name' => 'touhou']);
    $other = Tag::query()->create(['name' => 'other']);

    $firstPost->tags()->attach($touhou);
    $secondPost->tags()->attach($touhou);
    $currentPost->tags()->attach($touhou);
    $fourthPost->tags()->attach($touhou);
    $lastPost->tags()->attach($touhou);
    $otherPost->tags()->attach($other);

    $response = $this->get(route('posts.show', [
        'post' => $currentPost,
        'tags' => 'touhou',
    ]));

    $response->assertOk();
    $response->assertSee(route('posts.show', ['post' => $firstPost, 'tags' => 'touhou']), false);
    $response->assertSee(route('posts.show', ['post' => $lastPost, 'tags' => 'touhou']), false);
    $response->assertDontSee(route('posts.show', ['post' => $otherPost, 'tags' => 'touhou']), false);
    $response->assertSee(route('home', ['tags' => 'touhou']), false);
    $response->assertSee(route('posts.media.preview', $firstPost), false);
    $response->assertSee(route('posts.media.preview', $secondPost), false);
    $response->assertSee(route('posts.media.preview', $currentPost), false);
    $response->assertSee(route('posts.media.preview', $fourthPost), false);
    $response->assertSee(route('posts.media.preview', $lastPost), false);
    $response->assertDontSee(route('posts.media.preview', $otherPost), false);
});
