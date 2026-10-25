@props(['title' => null])
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f3f2f2">
    <meta name="description" content="Fridge Frenemy – der passiv-aggressive Kühlschrank-Butler gegen Lebensmittelverschwendung.">
    <title>{{ $title ? $title.' · ' : '' }}Fridge Frenemy</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/app-icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/app-icons/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:bg-ink focus:px-3 focus:py-2 focus:text-white">
        Zum Inhalt springen
    </a>

    {{ $slot }}

    {{-- Toast --}}
    <div x-data class="pointer-events-none fixed inset-x-0 bottom-[96px] z-30 mx-auto w-full max-w-[600px] px-4" aria-live="polite" role="status">
        <div x-show="$store.ui.toastText" x-cloak x-text="$store.ui.toastText"
             class="animate-ff-toast bg-ink px-4 py-3 text-sm font-semibold text-white"></div>
    </div>

    {{-- Push banner --}}
    <div x-data class="fixed inset-x-0 top-0 z-40 mx-auto w-full max-w-[600px] px-2.5 pt-2.5" aria-live="assertive">
        <button type="button" x-show="$store.ui.pushText" x-cloak @click="$store.ui.closePush()"
                class="flex w-full animate-ff-push gap-3 bg-ink px-4 py-3.5 text-left text-white shadow-lg"
                aria-label="Butler-Benachrichtigung schließen">
            <span class="flex h-9 w-9 flex-none items-center justify-center bg-accent">
                <x-icon name="bell" :size="20" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="flex justify-between text-xs font-bold uppercase tracking-[.1em] text-neutral-400">
                    <span>Kühlschrank-Butler</span><span x-text="$store.ui.pushTime"></span>
                </span>
                <span class="mt-1 block text-sm leading-[1.35]" x-text="$store.ui.pushText"></span>
            </span>
        </button>
    </div>

    @if (session('toast'))
        <div x-data x-init="$store.ui.toast(@js(session('toast')))"></div>
    @endif
</body>
</html>
