<x-layouts.app title="Butler">
    <x-page-title kicker="Täglich 08:00 Uhr" title="Butler-Tonalität" />

    <form method="POST" action="{{ route('butler.tone') }}" x-data x-ref="form">
        @csrf
        @method('PUT')
        <fieldset>
            <legend class="sr-only">Tonalität des Butlers wählen</legend>
            @foreach ($tones as $tone)
                @php($active = $household->passive_aggressive_tone === $tone)
                <label @class([
                    'grid cursor-pointer grid-cols-[28px_1fr] gap-2.5 border-t-2 border-ink px-5 py-3.5 focus-within:outline focus-within:outline-2 focus-within:-outline-offset-4 focus-within:outline-accent',
                    'bg-ink text-white' => $active,
                    'bg-transparent text-ink hover:bg-ink/5' => ! $active,
                ])>
                    <input type="radio" name="tone" value="{{ $tone->value }}" class="sr-only" @checked($active)
                           @change="$refs.form.requestSubmit()">
                    <span class="mt-0.5 flex h-5 w-5 items-center justify-center border-2 border-current" aria-hidden="true">
                        <span @class(['h-2.5 w-2.5', 'bg-accent' => $active])></span>
                    </span>
                    <span>
                        <span class="block text-lg font-extrabold">{{ $tone->label() }}</span>
                        <span class="mt-1 block text-[13px] font-medium leading-[1.4] opacity-95">„{{ $tone->example() }}“</span>
                    </span>
                </label>
            @endforeach
        </fieldset>
        <noscript>
            <div class="px-5 pb-4"><button type="submit" class="ff-btn-secondary">Tonalität speichern</button></div>
        </noscript>
    </form>

    <div class="flex flex-col gap-2.5 border-t-2 border-ink px-5 py-4" x-data="butlerTest(@js(route('butler.test')))">
        <button type="button" class="ff-btn-primary" @click="test()" :disabled="busy" :aria-busy="busy.toString()">
            <x-icon name="bell" />
            <span x-text="busy ? 'Butler formuliert …' : 'Alarm testen'">Alarm testen</span>
        </button>
        <p class="text-xs leading-normal text-neutral-700">
            Prüft Artikel mit Ablauf in ≤ 2 Tagen und schickt sie als Push an alle im Haushalt.
            Jeden Morgen um 08:00 Uhr macht der Butler das automatisch.
        </p>
    </div>
</x-layouts.app>
