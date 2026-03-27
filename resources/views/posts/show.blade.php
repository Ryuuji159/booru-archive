<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>booru archive</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700" rel="stylesheet"/>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-950 text-slate-100" style="font-family: 'Space Grotesk', sans-serif;">
@php
    $adminPanel = filament()->getPanel('admin');
    $adminLinkUrl = auth()->check() ? $adminPanel?->getUrl() : $adminPanel?->getLoginUrl();
    $adminLinkLabel = auth()->check() ? 'admin' : 'login';
    $backUrl = route('home', $navigationQuery);
    $rawTags = $navigationQuery['tags'] ?? '';
    $previousUrl = $previousPost ? route('posts.show', ['post' => $previousPost, ...$navigationQuery]) : null;
    $nextUrl = $nextPost ? route('posts.show', ['post' => $nextPost, ...$navigationQuery]) : null;
@endphp
<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6">
        <div class="mb-4 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="text-lg font-medium text-white transition hover:text-slate-200">
                booru archive
            </a>
            @if ($adminLinkUrl)
                <a href="{{ $adminLinkUrl }}" class="text-sm text-slate-400 transition hover:text-white">
                    {{ $adminLinkLabel }}
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('home') }}" class="flex flex-col gap-3 sm:flex-row">
            <input
                id="tags"
                name="tags"
                type="text"
                value="{{ $rawTags }}"
                placeholder="touhou rating:safe blonde_hair"
                class="min-w-0 flex-1 rounded-lg border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-500 focus:border-slate-400"
            />
            <div class="flex gap-3">
                <button
                    type="submit"
                    class="rounded-lg bg-white px-4 py-3 text-sm font-medium text-slate-950 transition hover:bg-slate-200"
                >
                    Buscar
                </button>
                @if ($rawTags !== '')
                    <a
                        href="{{ route('home') }}"
                        class="rounded-lg border border-white/10 px-4 py-3 text-sm text-slate-300 transition hover:bg-white/5"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

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

    <a href="{{ route('posts.media.full', $post) }}" target="_blank" rel="noreferrer" class="block">
        <section class="flex h-[60vh] items-center justify-center">
            <img
                src="{{ route('posts.media.full', $post) }}"
                alt="Post {{ $post->source_post_id }}"
                class="max-h-full max-w-full rounded-lg"
            >
        </section>
    </a>

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
</main>
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
</body>
</html>
