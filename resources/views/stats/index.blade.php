<x-layouts.app title="Bilanz">
    <x-page-title :kicker="$household->name" title="Bilanz" />

    <section class="grid grid-cols-[140px_1fr] border-t-2 border-ink" aria-label="Stimmung des Kühlschranks">
        <div @class([
            'flex items-center justify-center border-r-2 border-ink py-4 text-ink',
            'bg-accent-200' => $mood['mood'] === 'angry',
            'bg-surface' => $mood['mood'] !== 'angry',
        ])>
            <x-fridge-avatar :mood="$mood['mood']" :size="104" />
        </div>
        <div class="flex flex-col justify-center px-5 py-4">
            <p class="text-xs font-bold uppercase tracking-[.12em] text-neutral-700">Stimmung</p>
            <p class="mt-1 text-[26px] font-extrabold uppercase leading-none">{{ $mood['word'] }}</p>
            <p class="mt-2 text-[13px] font-medium leading-[1.4]">{{ $mood['line'] }}</p>
        </div>
    </section>

    <section class="grid grid-cols-2 border-y-2 border-ink" aria-label="Zähler">
        <div class="border-r-2 border-ink px-5 py-4">
            <h2 class="text-xs font-bold uppercase tracking-[.12em]">Helden-Counter</h2>
            <p class="mt-1.5 text-[64px] font-extrabold leading-none tracking-[-.03em]">{{ $stats->saved_count }}</p>
            <p class="mt-0.5 text-xs font-medium text-neutral-700">Artikel gerettet</p>
        </div>
        <div class="px-5 py-4">
            <h2 class="text-xs font-bold uppercase tracking-[.12em] text-accent-700">Schuld-Counter</h2>
            <p class="mt-1.5 text-[64px] font-extrabold leading-none tracking-[-.03em] text-accent">{{ $stats->trashed_count }}</p>
            <p class="mt-0.5 text-xs font-medium text-neutral-700">Opfer der Vergesslichkeit</p>
        </div>
    </section>

    <section class="px-5 py-4" aria-labelledby="quote-label">
        <div class="flex justify-between text-xs font-bold uppercase tracking-[.06em]">
            <h2 id="quote-label">Rettungsquote</h2><span>{{ $percent }}%</span>
        </div>
        <div class="mt-2 flex h-3.5 bg-accent" role="progressbar" aria-labelledby="quote-label"
             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
            <div class="bg-ink transition-[width] duration-[400ms]" style="width: {{ $percent }}%"></div>
        </div>
        <div class="mt-1.5 flex justify-between text-xs font-semibold text-neutral-700">
            <span>Gerettet</span><span>Verschwendet</span>
        </div>
    </section>
</x-layouts.app>
