@php
    $tags = $tags ?? [];
    $posts = is_array($posts ?? null) ? $posts : [];
@endphp

<div class="space-y-4">
    @if ($tags !== [])
        <div class="flex flex-wrap gap-2 items-center">
            <p class="font-medium">Tags: </p>
            @foreach ($tags as $tag)
                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200">
                    {{ $tag }}
                </span>
            @endforeach
        </div>
    @endif

    @if ($posts === [])
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">
            Konachan no devolvió resultados para esta muestra inicial o la consulta no pudo completarse.
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($posts as $post)
                @php
                    $postTags = array_values(array_filter(preg_split('/\s+/', trim((string) ($post['tags'] ?? ''))) ?: []));
                    $visibleTags = array_slice($postTags, 0, 6);
                    $previewUrl = $post['preview_url'] ?? $post['sample_url'] ?? $post['jpeg_url'] ?? $post['file_url'] ?? null;
                @endphp

                <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="aspect-[16/10] bg-gray-100">
                        @if ($previewUrl)
                            <img
                                src="{{ $previewUrl }}"
                                alt="Konachan post #{{ $post['id'] ?? 'unknown' }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            >
                        @else
                            <div class="flex h-full items-center justify-center text-sm text-gray-500">
                                Sin preview
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    #{{ $post['id'] ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $post['author'] ?? 'unknown author' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                    {{ strtoupper((string) ($post['rating'] ?? '?')) }}
                                </span>
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                    score {{ $post['score'] ?? 0 }}
                                </span>
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">
                                    {{ $post['status'] ?? 'unknown' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                <span class="block text-[11px] uppercase tracking-wide text-gray-400">Size</span>
                                <span>{{ $post['width'] ?? '-' }} x {{ $post['height'] ?? '-' }}</span>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                <span class="block text-[11px] uppercase tracking-wide text-gray-400">MD5</span>
                                <span
                                    class="font-mono">{{ \Illuminate\Support\Str::limit((string) ($post['md5'] ?? '-'), 12, '') }}</span>
                            </div>
                        </div>

                        @if ($visibleTags !== [])
                            <div class="flex flex-wrap gap-2">
                                @foreach ($visibleTags as $tag)
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex items-center gap-4 text-xs">
                            @if (filled($post['source'] ?? null))
                                <a
                                    href="{{ $post['source'] }}"
                                    target="_blank"
                                    rel="noreferrer noopener"
                                    class="font-medium text-blue-600 hover:text-blue-700"
                                >
                                    Source
                                </a>
                            @endif

                            @if (filled($post['file_url'] ?? null))
                                <a
                                    href="{{ $post['file_url'] }}"
                                    target="_blank"
                                    rel="noreferrer noopener"
                                    class="font-medium text-gray-600 hover:text-gray-800"
                                >
                                    Full image
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
