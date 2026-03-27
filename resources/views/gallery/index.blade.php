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
    $previousPageUrl = $posts->previousPageUrl();
    $nextPageUrl = $posts->nextPageUrl();
@endphp
<main class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
    <header class="mb-6">
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
    </header>

    <section>
        @if ($posts->isEmpty())
            <div class="py-16 text-center text-sm text-slate-400">
                No hay posts para esta búsqueda.
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
</main>
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
</body>
</html>
