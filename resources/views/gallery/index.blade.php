<x-layout>
    <x-header/>
    <x-search :raw-tags="$rawTags"/>

    <section>
        @php
            $previousPageUrl = $posts->previousPageUrl();
            $nextPageUrl = $posts->nextPageUrl();
        @endphp

        @if ($posts->isEmpty())
            <div class="py-16 text-center text-sm text-slate-400">
                No posts found for this search.
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($posts as $post)
                    @php
                        $previewUrl = route('posts.media.preview', $post);
                        $postUrl = route('posts.show', [
                            'post' => $post,
                            'tags' => $rawTags !== '' ? $rawTags : null,
                        ]);
                    @endphp

                    <a
                        href="{{ $postUrl }}"
                        class="block overflow-hidden rounded-lg bg-slate-900"
                    >
                        <img
                            src="{{ $previewUrl }}"
                            alt="Post {{ $post->source_post_id }}"
                            loading="lazy"
                            class="aspect-[4/3] h-full w-full object-cover transition hover:opacity-90"
                        >
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $posts->onEachSide(1)->links('pagination.gallery') }}
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('keydown', function (event) {
                if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.altKey) {
                    return;
                }

                const target = event.target;

                if (
                    target instanceof HTMLElement &&
                    (
                        target.isContentEditable ||
                        ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
                    )
                ) {
                    return;
                }

                if (event.key === 'ArrowLeft' && @js($previousPageUrl)) {
                    window.location.href = @js($previousPageUrl);
                }

                if (event.key === 'ArrowRight' && @js($nextPageUrl)) {
                    window.location.href = @js($nextPageUrl);
                }
            });
        </script>
    @endpush
</x-layout>
