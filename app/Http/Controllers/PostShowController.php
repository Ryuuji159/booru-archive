<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

        $previousPost = $this->resolvePreviousPost($navigationBaseQuery, $post);
        $nextPost = $this->resolveNextPost($navigationBaseQuery, $post);

        $contextPosts = $this->resolveContextPosts(
            post: $post,
            navigationBaseQuery: $navigationBaseQuery,
        );

        return view('posts.show', [
            'post' => $post,
            'previousPost' => $previousPost,
            'nextPost' => $nextPost,
            'contextPosts' => $contextPosts,
            'navigationQuery' => $navigationQuery,
            'rawTags' => $navigationQuery['tags'] ?? '',
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
    private function resolveContextPosts(Post $post, Builder $navigationBaseQuery): Collection
    {
        $previousPosts = $this->galleryPostsBefore($navigationBaseQuery, $post)
            ->limit(2)
            ->get();

        $nextPosts = $this->galleryPostsAfter($navigationBaseQuery, $post)
            ->limit(2)
            ->get();

        $contextPosts = $previousPosts
            ->push($post)
            ->merge($nextPosts)
            ->sortBy(fn (Post $contextPost): string => $this->galleryOrderKey($contextPost))
            ->values();

        $missing = 5 - $contextPosts->count();

        if ($missing > 0) {
            $excludedIds = $contextPosts->modelKeys();

            $additionalNextPosts = $this->galleryPostsAfter($navigationBaseQuery, $post)
                ->whereNotIn('id', $excludedIds)
                ->limit($missing)
                ->get();

            $contextPosts = $contextPosts
                ->merge($additionalNextPosts)
                ->sortBy(fn (Post $contextPost): string => $this->galleryOrderKey($contextPost))
                ->values();

            $missing = 5 - $contextPosts->count();

            if ($missing > 0) {
                $excludedIds = $contextPosts->modelKeys();

                $additionalPreviousPosts = $this->galleryPostsBefore($navigationBaseQuery, $post)
                    ->whereNotIn('id', $excludedIds)
                    ->limit($missing)
                    ->get()
                    ->sortBy(fn (Post $contextPost): string => $this->galleryOrderKey($contextPost))
                    ->values();

                $contextPosts = $additionalPreviousPosts
                    ->merge($contextPosts)
                    ->sortBy(fn (Post $contextPost): string => $this->galleryOrderKey($contextPost))
                    ->values();
            }
        }

        return $contextPosts;
    }

    private function resolvePreviousPost(Builder $navigationBaseQuery, Post $post): ?Post
    {
        return $this->galleryPostsBefore($navigationBaseQuery, $post)
            ->first();
    }

    private function resolveNextPost(Builder $navigationBaseQuery, Post $post): ?Post
    {
        return $this->galleryPostsAfter($navigationBaseQuery, $post)
            ->first();
    }

    private function galleryPostsBefore(Builder $navigationBaseQuery, Post $post): Builder
    {
        $query = clone $navigationBaseQuery;

        if ($post->source_created_at === null) {
            return $query
                ->whereNull('source_created_at')
                ->where('id', '<', $post->getKey())
                ->orderByDesc('id');
        }

        return $query
            ->where(function (Builder $query) use ($post): void {
                $query
                    ->whereNull('source_created_at')
                    ->orWhere('source_created_at', '<', $post->source_created_at)
                    ->orWhere(function (Builder $query) use ($post): void {
                        $query
                            ->where('source_created_at', $post->source_created_at)
                            ->where('id', '<', $post->getKey());
                    });
            })
            ->orderByDesc('source_created_at')
            ->orderByDesc('id');
    }

    private function galleryPostsAfter(Builder $navigationBaseQuery, Post $post): Builder
    {
        $query = clone $navigationBaseQuery;

        if ($post->source_created_at === null) {
            return $query
                ->where(function (Builder $query) use ($post): void {
                    $query
                        ->whereNull('source_created_at')
                        ->where('id', '>', $post->getKey())
                        ->orWhereNotNull('source_created_at');
                })
                ->orderBy('source_created_at')
                ->orderBy('id');
        }

        return $query
            ->where(function (Builder $query) use ($post): void {
                $query
                    ->where('source_created_at', '>', $post->source_created_at)
                    ->orWhere(function (Builder $query) use ($post): void {
                        $query
                            ->where('source_created_at', $post->source_created_at)
                            ->where('id', '>', $post->getKey());
                    });
            })
            ->orderBy('source_created_at')
            ->orderBy('id');
    }

    private function galleryOrderKey(Post $post): string
    {
        $sourceCreatedAt = $post->source_created_at?->getTimestamp() ?? 0;

        return sprintf('%020d:%020d', $sourceCreatedAt, $post->getKey());
    }
}
