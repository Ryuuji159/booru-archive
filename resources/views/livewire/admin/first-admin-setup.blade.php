<div class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-10 sm:px-6 lg:px-8">
    <div class="w-full rounded-2xl border border-gray-200 bg-white px-6 py-8 shadow-sm sm:px-8 sm:py-9">
        <div class="mb-7 space-y-3">
            <p class="text-xs font-medium uppercase tracking-[0.28em] text-gray-500">Initial setup</p>
            <h1 class="text-3xl font-semibold tracking-tight text-gray-950 sm:text-[2.1rem]">
                Create the first administrator
            </h1>
            <p class="max-w-lg text-sm leading-6 text-gray-500">
                This form only exists until the first user is created.
            </p>
        </div>

        <form wire:submit="submit" class="space-y-7">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                {{ $this->form }}
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-primary-400 px-4 py-3.5 text-sm font-semibold text-slate-950 transition duration-150 hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-white"
            >
                Create administrator
            </button>
        </form>
    </div>
</div>
