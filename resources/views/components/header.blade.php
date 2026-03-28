@php
    $adminPanel = filament()->getPanel('admin');
    $adminLinkUrl = auth()->check() ? $adminPanel?->getUrl() : $adminPanel?->getLoginUrl();
    $adminLinkLabel = auth()->check() ? 'admin' : 'login';
@endphp

<header class="mb-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="text-lg font-medium text-white transition hover:text-slate-200">
                {{ config('app.name') }}
            </a>
        </div>
        <div class="flex items-center gap-4">
            @if ($adminLinkUrl)
                <a href="{{ $adminLinkUrl }}" class="text-sm text-slate-400 transition hover:text-white">
                    {{ $adminLinkLabel }}
                </a>
            @endif
        </div>
    </div>
</header>
