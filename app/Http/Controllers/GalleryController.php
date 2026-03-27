<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $rawTags = trim((string) $request->query('tags', ''));

        $tags = collect(preg_split('/[\s,]+/', $rawTags) ?: [])
            ->map(fn (string $tag): string => trim(strtolower($tag)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $posts = Post::query()
            ->readyForGallery()
            ->matchingAllTags($tags)
            ->with(['tags' => fn ($query) => $query->orderBy('name')])
            ->orderedForGallery()
            ->paginate(5 * 20)
            ->withQueryString();

        return view('gallery.index', [
            'posts' => $posts,
            'rawTags' => $rawTags,
            'tags' => $tags,
        ]);
    }
}
