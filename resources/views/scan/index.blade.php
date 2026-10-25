<x-layouts.app title="Scan">
    <div x-data="scanPage({{ Js::from([
        'lookupUrl' => route('scan.lookup'),
        'phase' => $errors->any() ? 'found' : 'idle',
        'old' => [
            'barcode' => old('barcode'),
            'name' => old('name'),
            'category' => old('category'),
            'quantity' => old('quantity'),
            'shelf_days' => old('shelf_days'),
        ],
    ]) }})">
        <x-page-title kicker="Einlagern" title="Neuer Verdächtiger" class="pb-3.5" />

        <p x-show="notice" x-cloak x-text="notice" role="alert"
           class="mx-5 mb-3 border-2 border-accent bg-accent-100 px-4 py-3 text-[13px] font-semibold text-accent-700"></p>

        {{-- Viewfinder --}}
        <div x-show="phase !== 'found'">
            <div class="relative mx-5 h-[230px] overflow-hidden bg-ink">
                <div id="ff-reader" class="absolute inset-0" x-show="phase === 'scanning'"></div>
                <div class="pointer-events-none absolute inset-x-9 top-[27px] h-[176px] overflow-hidden">
                    <div x-show="phase === 'scanning'" class="h-[3px] animate-ff-scan bg-accent shadow-[0_0_12px_#ec3013]"></div>
                </div>
                <div class="pointer-events-none absolute left-[30px] top-[21px] h-7 w-7 border-l-3 border-t-3 border-white"></div>
                <div class="pointer-events-none absolute right-[30px] top-[21px] h-7 w-7 border-r-3 border-t-3 border-white"></div>
                <div class="pointer-events-none absolute bottom-[21px] left-[30px] h-7 w-7 border-b-3 border-l-3 border-white"></div>
                <div class="pointer-events-none absolute bottom-[21px] right-[30px] h-7 w-7 border-b-3 border-r-3 border-white"></div>
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs font-semibold uppercase tracking-[.1em] text-neutral-400"
                     x-show="phase !== 'scanning'">Barcode im Rahmen platzieren</div>
                <div class="pointer-events-none absolute inset-x-0 bottom-2 text-center text-xs font-semibold uppercase tracking-[.1em] text-white"
                     x-show="phase === 'scanning'" x-cloak aria-live="polite">Suche Barcode …</div>
            </div>
            <div class="flex flex-col gap-2.5 px-5 py-4">
                <button type="button" class="ff-btn-primary" @click="startScan()" x-show="phase !== 'scanning'">
                    <x-icon name="scan-barcode" />Barcode scannen
                </button>
                <button type="button" class="ff-btn-secondary" @click="cancel()" x-show="phase === 'scanning'" x-cloak>
                    Scan abbrechen
                </button>
                <button type="button" class="ff-btn-secondary" @click="manual()">Manuell eintragen</button>
            </div>
            <p class="mx-5 border-t-2 border-ink pt-3 text-xs leading-normal text-neutral-700">
                Produktname und Kategorie kommen von Open Food Facts. Haltbarkeit startet bei +7 Tagen — du weißt es sowieso besser.
            </p>
        </div>

        {{-- Hit / manual form --}}
        <form x-show="phase === 'found'" x-cloak method="POST" action="{{ route('fridge.store') }}" class="border-t-2 border-ink" novalidate>
            @csrf
            <input type="hidden" name="barcode" :value="form.barcode">
            <input type="hidden" name="category" :value="form.category">
            <input type="hidden" name="quantity" :value="form.quantity">
            <input type="hidden" name="shelf_days" :value="form.shelf">

            <div x-show="form.fromScan" class="flex items-center justify-between border-b-2 border-ink bg-surface px-5 py-3">
                <span class="text-xs font-bold uppercase tracking-[.08em]">Treffer · Open Food Facts</span>
                <span class="text-xs font-semibold tabular-nums text-neutral-700" x-text="form.barcode"></span>
            </div>

            <div class="flex flex-col gap-4 px-5 py-4">
                <div>
                    <label for="name" class="ff-label">Produkt</label>
                    <input id="name" name="name" type="text" class="ff-input" x-ref="name" x-model="form.name"
                           placeholder="z. B. Hüttenkäse" maxlength="120" required autocomplete="off"
                           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')<p id="name-error" class="ff-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div role="group" aria-labelledby="qty-label">
                        <p id="qty-label" class="ff-label">Menge</p>
                        <div class="grid grid-cols-[44px_1fr_44px] border-2 border-ink">
                            <button type="button" class="ff-stepper-btn" @click="form.quantity = Math.max(1, form.quantity - 1)"
                                    :disabled="form.quantity <= 1" aria-label="Menge verringern"><x-icon name="minus" :size="18" /></button>
                            <output class="flex items-center justify-center text-base font-bold" x-text="form.quantity" aria-live="polite"></output>
                            <button type="button" class="ff-stepper-btn" @click="form.quantity++" aria-label="Menge erhöhen"><x-icon name="plus" :size="18" /></button>
                        </div>
                        @error('quantity')<p class="ff-error">{{ $message }}</p>@enderror
                    </div>
                    <div role="group" aria-labelledby="shelf-label">
                        <p id="shelf-label" class="ff-label">Haltbar bis</p>
                        <div class="grid grid-cols-[44px_1fr_44px] border-2 border-ink">
                            <button type="button" class="ff-stepper-btn" @click="form.shelf = Math.max(0, form.shelf - 1)"
                                    :disabled="form.shelf <= 0" aria-label="Einen Tag früher"><x-icon name="minus" :size="18" /></button>
                            <output class="flex items-center justify-center text-sm font-bold" x-text="dateLabel" aria-live="polite"></output>
                            <button type="button" class="ff-stepper-btn" @click="form.shelf++" aria-label="Einen Tag später"><x-icon name="plus" :size="18" /></button>
                        </div>
                        @error('shelf_days')<p class="ff-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <p class="text-[13px] font-medium text-accent-700">Butler: „<span x-text="remark"></span>“</p>

                <div class="flex flex-col gap-2.5">
                    <button type="submit" class="ff-btn-primary" :disabled="!form.name.trim()">
                        <x-icon name="check" />Einlagern
                    </button>
                    <button type="button" class="ff-btn-secondary" @click="cancel()">Abbrechen</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
