@props([
    'rawTags' => '',
])

<form method="GET" action="{{ route('home') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
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
        @if (filled($rawTags))
            <a
                href="{{ route('home') }}"
                class="rounded-lg border border-white/10 px-4 py-3 text-sm text-slate-300 transition hover:bg-white/5"
            >
                Limpiar
            </a>
        @endif
    </div>
</form>
