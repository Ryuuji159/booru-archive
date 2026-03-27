<?php

use App\Models\Post;
use App\Models\Tag;

it('lists downloaded posts on the home page and filters them by all searched tags', function () {
    $matchingPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1000,
        'md5' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'file_ext' => 'jpg',
        'author' => 'Oyashiro-sama',
        'width' => 1600,
        'height' => 1200,
        'source_file_url' => 'https://konachan.com/image/post-1000.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/aa/aa/aa/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg',
        'preview_path' => 'preview/aa/aa/aa/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $partialMatchPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1001,
        'md5' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        'file_ext' => 'jpg',
        'author' => 'Konata',
        'width' => 1400,
        'height' => 900,
        'source_file_url' => 'https://konachan.com/image/post-1001.jpg',
        'storage_disk' => 'local',
        'storage_path' => 'full/bb/bb/bb/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb.jpg',
        'preview_path' => 'preview/bb/bb/bb/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb.jpg',
        'download_status' => Post::STATUS_DOWNLOADED,
    ]);

    $notReadyPost = Post::query()->create([
        'source_site' => 'konachan',
        'source_post_id' => 1002,
        'md5' => 'cccccccccccccccccccccccccccccccc',
        'file_ext' => 'jpg',
        'source_file_url' => 'https://konachan.com/image/post-1002.jpg',
        'download_status' => Post::STATUS_FAILED,
    ]);

    $touhou = Tag::query()->create(['name' => 'touhou']);
    $blondeHair = Tag::query()->create(['name' => 'blonde_hair']);
    $redEyes = Tag::query()->create(['name' => 'red_eyes']);

    $matchingPost->tags()->attach([$touhou->id, $blondeHair->id, $redEyes->id]);
    $partialMatchPost->tags()->attach([$touhou->id, $redEyes->id]);
    $notReadyPost->tags()->attach([$touhou->id, $blondeHair->id]);

    $response = $this->get(route('home', ['tags' => 'touhou blonde_hair']));

    $response->assertOk();
    $response->assertSee(route('posts.media.preview', $matchingPost), false);
    $response->assertSee(route('posts.show', $matchingPost), false);
    $response->assertDontSee(route('posts.media.preview', $partialMatchPost), false);
    $response->assertDontSee(route('posts.media.preview', $notReadyPost), false);
    $response->assertSee('value="touhou blonde_hair"', false);
});
