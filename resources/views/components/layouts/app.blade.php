@props(['title' => null])
<x-layouts.base :title="$title">
    <div class="mx-auto flex min-h-dvh w-full max-w-[600px] flex-col bg-bg sm:border-x-2 sm:border-ink">
        <main id="main" class="flex-1 pb-[110px]">
            {{ $slot }}
        </main>

        <nav aria-label="Hauptnavigation"
             class="fixed inset-x-0 bottom-0 z-20 mx-auto grid w-full max-w-[600px] grid-cols-4 border-t-2 border-ink bg-bg pb-[max(18px,env(safe-area-inset-bottom))] sm:border-x-2">
            <x-tab-link route="fridge.index" icon="refrigerator" label="Fach" />
            <x-tab-link route="scan.index" icon="scan-barcode" label="Scan" />
            <x-tab-link route="butler.index" icon="bell" label="Butler" />
            <x-tab-link route="stats.index" icon="bar-chart-3" label="Bilanz" />
        </nav>
    </div>
</x-layouts.base>
