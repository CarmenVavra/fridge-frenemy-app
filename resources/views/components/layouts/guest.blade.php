@props(['title' => null, 'kicker' => 'Fridge Frenemy'])
<x-layouts.base :title="$title">
    <div class="mx-auto flex min-h-dvh w-full max-w-[600px] flex-col bg-bg sm:border-x-2 sm:border-ink">
        <div class="flex items-center gap-4 border-b-2 border-ink px-5 py-5">
            <x-fridge-avatar mood="meh" :size="56" class="flex-none" />
            <div>
                <p class="text-[15px] font-extrabold uppercase tracking-[.12em]">Fridge Frenemy</p>
                <p class="text-[13px] font-medium text-neutral-700">Der passiv-aggressive Kühlschrank-Butler.</p>
            </div>
        </div>
        <main id="main" class="flex-1 pb-10">
            <x-page-title :kicker="$kicker" :title="$title ?? ''" class="pt-6" />
            @if (session('status'))
                <p class="mx-5 mb-4 border-2 border-ink bg-surface px-4 py-3 text-sm font-semibold" role="status">{{ session('status') }}</p>
            @endif
            {{ $slot }}
        </main>
        <footer class="flex flex-wrap items-center justify-between gap-3 border-t-2 border-ink px-5 py-4">
            @auth
                <a href="{{ route('fridge.index') }}" class="text-[13px] font-bold text-accent-700 underline underline-offset-4 hover:text-accent-600">Zurück zur App</a>
            @endauth
            <x-legal-links />
        </footer>
    </div>
</x-layouts.base>
