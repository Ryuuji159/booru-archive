@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-6 text-sm text-slate-400">
        <div class="hidden sm:block">
            <p>
                Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results
            </p>
        </div>

        <div class="flex items-center gap-5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 min-w-6 items-center justify-center select-none text-slate-700" aria-hidden="true">
                    &lsaquo;
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-8 min-w-6 items-center justify-center transition hover:text-white" aria-label="{{ __('Previous page') }}">
                    &lsaquo;
                </a>
            @endif

            <div class="flex items-center gap-4">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex h-8 min-w-6 items-center justify-center select-none text-slate-600">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="relative inline-flex h-8 min-w-6 items-center justify-center font-medium text-white after:absolute after:bottom-0 after:left-0 after:right-0 after:h-px after:bg-white">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex h-8 min-w-6 items-center justify-center transition hover:text-white" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-8 min-w-6 items-center justify-center transition hover:text-white" aria-label="{{ __('Next page') }}">
                    &rsaquo;
                </a>
            @else
                <span class="inline-flex h-8 min-w-6 items-center justify-center select-none text-slate-700" aria-hidden="true">
                    &rsaquo;
                </span>
            @endif
        </div>
    </nav>
@endif
