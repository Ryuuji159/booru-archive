<x-layout :title="$post->tags->isNotEmpty() ? $post->tags->pluck('name')->implode(' ') : 'Post #'.$post->source_post_id">
    <x-header/>
    <x-search :raw-tags="$rawTags"/>

    @php
        $backUrl = route('home', $navigationQuery);
        $previousUrl = $previousPost ? route('posts.show', ['post' => $previousPost, ...$navigationQuery]) : null;
        $nextUrl = $nextPost ? route('posts.show', ['post' => $nextPost, ...$navigationQuery]) : null;
    @endphp

    <header class="mb-6 flex items-center justify-between gap-4">
        <a href="{{ $backUrl }}" class="text-sm text-slate-400 transition hover:text-white">
            back
        </a>
        <div class="flex items-center gap-4">
            @if ($previousUrl)
                <a href="{{ $previousUrl }}" class="text-sm text-slate-400 transition hover:text-white">
                    prev
                </a>
            @endif
            @if ($nextUrl)
                <a href="{{ $nextUrl }}" class="text-sm text-slate-400 transition hover:text-white">
                    next
                </a>
            @endif
        </div>
    </header>

    <section class="relative">
        <div class="flex h-[60vh] items-center justify-center">
            <img
                src="{{ route('posts.media.full', $post) }}"
                alt="Post {{ $post->source_post_id }}"
                class="max-h-[60vh] max-w-full rounded-lg"
            >
        </div>

        <a
            href="{{ route('posts.media.full', $post) }}"
            target="_blank"
            rel="noreferrer"
            aria-label="Open full image"
            class="absolute inset-y-0 left-1/4 z-20 w-1/2 touch-manipulation rounded-lg focus-visible:outline-none"
        ></a>

        @if ($previousUrl)
            <a
                href="{{ $previousUrl }}"
                aria-label="Previous post"
                class="absolute inset-y-0 left-0 z-10 w-1/4 touch-manipulation rounded-l-lg transition hover:bg-black/10 focus-visible:bg-black/10 focus-visible:outline-none"
            ></a>
        @endif

        @if ($nextUrl)
            <a
                href="{{ $nextUrl }}"
                aria-label="Next post"
                class="absolute inset-y-0 right-0 z-10 w-1/4 touch-manipulation rounded-r-lg transition hover:bg-black/10 focus-visible:bg-black/10 focus-visible:outline-none"
            ></a>
        @endif
    </section>

    @if ($contextPosts->isNotEmpty())
        <section class="mt-4">
            <div class="grid grid-cols-5 gap-3">
                @foreach ($contextPosts as $contextPost)
                    @php
                        $contextUrl = route('posts.show', ['post' => $contextPost, ...$navigationQuery]);
                        $previewUrl = route('posts.media.preview', $contextPost);
                        $isCurrent = $contextPost->is($post);
                    @endphp

                    <a
                        href="{{ $contextUrl }}"
                        class="block overflow-hidden rounded-md bg-slate-900 {{ $isCurrent ? 'ring-1 ring-white' : '' }}"
                    >
                        <img
                            src="{{ $previewUrl }}"
                            alt="Post {{ $contextPost->source_post_id }}"
                            loading="lazy"
                            class="aspect-[4/3] h-full w-full object-cover {{ $isCurrent ? '' : 'opacity-80 transition hover:opacity-100' }}"
                        >
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-6 grid gap-6 border-t border-white/10 pt-6 md:grid-cols-[160px_1fr]">
        <div class="text-sm text-slate-500">
            details
        </div>

        <div class="space-y-5 text-sm text-slate-300">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <div class="text-slate-500">source</div>
                    <div>{{ $post->source_site }} #{{ $post->source_post_id }}</div>
                </div>
                <div>
                    <div class="text-slate-500">author</div>
                    <div>{{ $post->author ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-slate-500">rating</div>
                    <div>{{ $post->rating ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-slate-500">resolution</div>
                    <div>{{ $post->width && $post->height ? "{$post->width} x {$post->height}" : '-' }}</div>
                </div>
                <div>
                    <div class="text-slate-500">file</div>
                    <div>
                        {{ $post->file_ext ? strtoupper($post->file_ext) : '-' }}
                        @if ($post->file_size)
                            · {{ number_format($post->file_size) }} bytes
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-slate-500">created</div>
                    <div>{{ $post->source_created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                </div>
            </div>

            <div>
                <div class="mb-2 text-slate-500">tags</div>
                <div class="flex flex-wrap gap-2">
                    @forelse ($post->tags as $tag)
                        <a
                            href="{{ route('home', ['tags' => $tag->name]) }}"
                            class="rounded-md bg-white/5 px-2 py-1 text-xs text-slate-300 transition hover:bg-white/10 hover:text-white"
                        >
                            {{ $tag->name }}
                        </a>
                    @empty
                        <span>-</span>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('keydown', function (event) {
                if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.altKey) {
                    return;
                }

                if (event.key === 'ArrowLeft' && @js($previousUrl)) {
                    window.location.href = @js($previousUrl);
                }

                if (event.key === 'ArrowRight' && @js($nextUrl)) {
                    window.location.href = @js($nextUrl);
                }
            });
        </script>
    @endpush
</x-layout>
