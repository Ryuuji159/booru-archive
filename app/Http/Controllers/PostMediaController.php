<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PostMediaController extends Controller
{
    public function preview(Post $post): BinaryFileResponse
    {
        return $this->serveMedia($post, $post->preview_path);
    }

    public function full(Post $post): BinaryFileResponse
    {
        return $this->serveMedia($post, $post->storage_path);
    }

    private function serveMedia(Post $post, ?string $path): BinaryFileResponse
    {
        abort_unless(
            $post->download_status === Post::STATUS_DOWNLOADED && filled($path),
            Response::HTTP_NOT_FOUND,
        );

        $disk = Storage::disk($post->storage_disk ?: 'local');

        abort_unless($disk->exists($path), Response::HTTP_NOT_FOUND);

        return response()->file(
            $disk->path($path),
            [
                'Cache-Control' => 'public, max-age=86400',
            ],
        );
    }
}
