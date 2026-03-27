<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class PostShowController extends Controller
{
    public function __invoke(Request $request, Post $post): View
    {
        abort_unless(
            $post->download_status === Post::STATUS_DOWNLOADED
                && filled($post->storage_path)
                && filled($post->preview_path),
            Response::HTTP_NOT_FOUND,
        );

        $post->load([
            'tags' => fn ($query) => $query->orderBy('name'),
        ]);

        $tags = $this->parseTags($request->query('tags', ''));
        $navigationQuery = $tags === [] ? [] : ['tags' => implode(' ', $tags)];
        $navigationBaseQuery = Post::query()
            ->readyForGallery()
            ->matchingAllTags($tags);
        $keyName = $post->getQualifiedKeyName();

        $previousPost = (clone $navigationBaseQuery)
            ->where($keyName, '<', $post->getKey())
            ->orderByDesc('id')
            ->first();

        $nextPost = (clone $navigationBaseQuery)
            ->where($keyName, '>', $post->getKey())
            ->orderedForGallery()
            ->first();

        $contextPosts = $this->resolveContextPosts(
            post: $post,
            navigationBaseQuery: $navigationBaseQuery,
            keyName: $keyName,
        );

        return view('posts.show', [
            'post' => $post,
            'previousPost' => $previousPost,
            'nextPost' => $nextPost,
            'contextPosts' => $contextPosts,
            'navigationQuery' => $navigationQuery,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function parseTags(string $rawTags): array
    {
        return collect(preg_split('/[\s,]+/', trim($rawTags)) ?: [])
            ->map(fn (string $tag): string => trim(strtolower($tag)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Post>
     */
    private function resolveContextPosts(Post $post, $navigationBaseQuery, string $keyName): Collection
    {
        $previousPosts = (clone $navigationBaseQuery)
            ->where($keyName, '<', $post->getKey())
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        $nextPosts = (clone $navigationBaseQuery)
            ->where($keyName, '>', $post->getKey())
            ->orderedForGallery()
            ->limit(2)
            ->get();

        $contextPosts = $previousPosts
            ->push($post)
            ->merge($nextPosts)
            ->sortBy('id')
            ->values();

        $missing = 5 - $contextPosts->count();

        if ($missing > 0) {
            $excludedIds = $contextPosts->modelKeys();

            $additionalNextPosts = (clone $navigationBaseQuery)
                ->whereNotIn($keyName, $excludedIds)
                ->where($keyName, '>', $post->getKey())
                ->orderedForGallery()
                ->limit($missing)
                ->get();

            $contextPosts = $contextPosts
                ->merge($additionalNextPosts)
                ->sortBy('id')
                ->values();

            $missing = 5 - $contextPosts->count();

            if ($missing > 0) {
                $excludedIds = $contextPosts->modelKeys();

                $additionalPreviousPosts = (clone $navigationBaseQuery)
                    ->whereNotIn($keyName, $excludedIds)
                    ->where($keyName, '<', $post->getKey())
                    ->orderByDesc('id')
                    ->limit($missing)
                    ->get()
                    ->sortBy('id')
                    ->values();

                $contextPosts = $additionalPreviousPosts
                    ->merge($contextPosts)
                    ->sortBy('id')
                    ->values();
            }
        }

        return $contextPosts;
    }
}
