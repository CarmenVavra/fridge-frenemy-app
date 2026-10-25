<x-layouts.app title="Fach">
    <div x-data="fridgeDashboard({{ Js::from([
        'items' => $cards,
        'saved' => $stats->saved_count,
        'trashed' => $stats->trashed_count,
        'alert' => $alert ? [
            'message' => $alert->message,
            'time' => $alert->created_at->format('H:i'),
            'dismiss_url' => route('butler.alerts.dismiss', $alert),
        ] : null,
    ]) }})">
        {{-- Header --}}
        <header class="flex items-baseline justify-between gap-3 px-5 pb-3 pt-3">
            <h1 class="text-[15px] font-extrabold uppercase tracking-[.12em]">Fridge Frenemy</h1>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-1.5 text-xs font-semibold text-neutral-700 underline-offset-4 hover:text-ink hover:underline"
               aria-label="Profil und Haushalt {{ $household->name }}">
                <x-icon name="user" :size="14" />
                {{ $household->name }}
            </a>
        </header>

        {{-- Aggro-Barometer --}}
        <section aria-labelledby="baro-title"
                 class="border-y-2 border-ink px-5 pb-[18px] pt-4 transition-colors"
                 :class="{
                     'bg-surface text-ink': baro.level === 1,
                     'bg-accent-200 text-ink': baro.level === 2,
                     'bg-accent text-white animate-ff-pulse': baro.level === 3,
                 }">
            <div class="flex justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="baro-title" class="ff-kicker opacity-90">Aggro-Barometer</h2>
                    <p class="mt-1.5 text-[38px] font-extrabold uppercase leading-[.95] tracking-[-.02em]" x-text="baro.word"></p>
                </div>
                <svg width="72" height="72" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"
                     class="flex-none" :class="baro.level === 3 && 'animate-ff-shake-fast'" role="img" :aria-label="'Kühlschrank ist ' + baro.word">
                    <rect x="20" y="6" width="60" height="88" :fill="baro.fill"></rect>
                    <path d="M20 34H80"></path><path d="M70 16V26M70 44V62"></path>
                    <circle cx="40" cy="58" r="4" fill="currentColor" stroke="none"></circle>
                    <circle cx="58" cy="58" r="4" fill="currentColor" stroke="none"></circle>
                    <path :d="baro.brows"></path><path :d="baro.mouth"></path>
                </svg>
            </div>
            <p class="mt-2.5 text-sm font-medium leading-[1.4]" x-text="baro.line"></p>
            <div class="mt-3.5 grid grid-cols-3 gap-1" aria-hidden="true">
                <div class="h-1.5 bg-current"></div>
                <div class="h-1.5 bg-current" :class="baro.level >= 2 ? 'opacity-100' : 'opacity-20'"></div>
                <div class="h-1.5 bg-current" :class="baro.level >= 3 ? 'opacity-100' : 'opacity-20'"></div>
            </div>
        </section>

        {{-- Counters --}}
        <div class="grid grid-cols-2 border-b-2 border-ink">
            <div class="border-r-2 border-ink px-5 py-3">
                <p class="text-xs font-bold uppercase tracking-[.12em] text-neutral-700">Gerettet</p>
                <p class="text-[28px] font-extrabold leading-none" x-text="saved">{{ $stats->saved_count }}</p>
            </div>
            <div class="px-5 py-3 text-accent-700">
                <p class="text-xs font-bold uppercase tracking-[.12em]">Schuld</p>
                <p class="text-[28px] font-extrabold leading-none" x-text="trashed">{{ $stats->trashed_count }}</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="grid grid-cols-3 border-b-2 border-ink" role="group" aria-label="Artikel filtern">
            @foreach (['all' => ['Alle', 'items'], 'crit' => ['Kritisch', 'crit'], 'fresh' => ['Frisch', 'fresh']] as $key => [$label, $list])
                <button type="button" @click="filter = '{{ $key }}'" :aria-pressed="filter === '{{ $key }}'"
                        @class(['cursor-pointer py-3 text-[13px] font-bold', 'border-r-2 border-ink' => ! $loop->last])
                        :class="filter === '{{ $key }}' ? 'bg-ink text-white' : 'bg-transparent text-ink hover:bg-ink/5'">
                    {{ $label }} <span x-text="{{ $list }}.length"></span>
                </button>
            @endforeach
        </div>

        {{-- Empty state --}}
        <p x-show="isEmpty" x-cloak class="px-5 py-12 text-[22px] font-extrabold leading-[1.15]">
            Nichts zu sehen.<br><span class="text-neutral-600">Verdächtig ordentlich.</span>
        </p>

        {{-- Item cards --}}
        <ul aria-label="Artikel im Kühlschrank">
            <template x-for="item in items" :key="item.id">
                <li x-show="isVisible(item.id)"
                    class="transition duration-[450ms] ease-in"
                    :class="{
                        'translate-y-[130%] opacity-0': leaving[item.id] === 'trash',
                        'scale-90 opacity-0': leaving[item.id] === 'eat',
                    }">
                    <div class="relative grid cursor-pointer grid-cols-[92px_1fr] border-b-2 border-ink"
                         :class="item.days < 0 ? 'bg-surface' : 'bg-transparent'"
                         role="button" tabindex="0" :aria-expanded="isOpen(item).toString()"
                         :aria-label="item.name + ', ' + (item.days < 0 ? 'seit ' + (-item.days) + ' Tagen abgelaufen' : item.days === 0 ? 'läuft heute ab' : 'noch ' + item.days + ' Tage') + '. Aktionen ein- oder ausblenden'"
                         @click="toggle(item.id)" @keydown.enter.prevent="toggle(item.id)" @keydown.space.prevent="toggle(item.id)">
                        <div class="py-3.5 pl-5" :class="item.days < 0 && 'opacity-[.55]'">
                            <p class="text-[34px] font-extrabold leading-none tracking-[-.03em]"
                               :class="item.stage >= 4 ? 'text-accent' : 'text-ink'"
                               x-text="item.days < 0 ? '−' + (-item.days) : item.days"></p>
                            <p class="mt-1 text-xs font-bold uppercase tracking-[.06em] text-neutral-700"
                               x-text="item.days < 0 ? 'Überfällig' : item.days === 0 ? 'Heute' : item.days === 1 ? 'Tag' : 'Tage'"></p>
                            <template x-if="item.days >= 0 && item.days <= 1">
                                <span class="mt-1.5 block animate-ff-shake text-accent" title="Schweißausbruch">
                                    <x-icon name="droplets" :size="22" />
                                </span>
                            </template>
                        </div>
                        <div class="min-w-0 py-3.5 pl-1 pr-5" :class="item.days < 0 && 'opacity-[.55]'">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-[19px] font-extrabold leading-[1.1] tracking-[-.01em]" x-text="item.name"></h3>
                                <div class="flex flex-none gap-0.5 pt-[5px]" role="img" :aria-label="'Gefahrenstufe ' + item.stage + ' von 5'">
                                    <template x-for="n in 5" :key="n">
                                        <div class="h-3 w-[7px]"
                                             :class="n <= item.stage ? (item.stage >= 4 ? 'bg-accent' : 'bg-ink') : 'bg-neutral-300'"></div>
                                    </template>
                                </div>
                            </div>
                            <p class="mt-[3px] text-xs font-medium text-neutral-700" x-text="item.meta"></p>
                            <template x-if="item.quote">
                                <p class="mt-2 text-[13px] font-medium leading-[1.38] text-accent-700"
                                   :class="item.days < 0 && 'pr-[120px]'" x-text="'„' + item.quote + '“'"></p>
                            </template>
                        </div>
                        <template x-if="item.days < 0">
                            <span class="ff-rip" aria-hidden="true">R.I.P.</span>
                        </template>
                    </div>

                    <div x-show="isOpen(item)" class="grid grid-cols-2 border-b-2 border-ink">
                        <button type="button" @click.stop="act(item, 'eat', $event)"
                                class="flex cursor-pointer items-center gap-2.5 border-r-2 border-ink bg-ink px-5 py-3.5 text-left text-sm font-bold text-white hover:bg-neutral-800"
                                :aria-label="item.name + ' aufgegessen'">
                            <x-icon name="utensils" :size="18" />Aufgegessen
                        </button>
                        <button type="button" @click.stop="act(item, 'trash', $event)"
                                class="flex cursor-pointer items-center gap-2.5 bg-accent px-5 py-3.5 text-left text-sm font-bold text-white hover:bg-accent-600"
                                :aria-label="item.name + ' weggeworfen'">
                            <x-icon name="trash-2" :size="18" />Weggeworfen
                        </button>
                    </div>
                </li>
            </template>
        </ul>

        <noscript>
            <ul class="px-5 py-4">
                @foreach ($cards as $card)
                    <li>{{ $card['name'] }} – {{ $card['days'] }} Tage</li>
                @endforeach
            </ul>
        </noscript>
    </div>
</x-layouts.app>
