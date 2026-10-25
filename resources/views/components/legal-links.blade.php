<nav aria-label="Rechtliches" {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-5 gap-y-2 text-[13px] font-semibold']) }}>
    <a href="{{ route('legal.imprint') }}" class="text-neutral-700 underline underline-offset-4 hover:text-accent-700"
       @if (request()->routeIs('legal.imprint')) aria-current="page" @endif>Impressum</a>
    <a href="{{ route('legal.privacy') }}" class="text-neutral-700 underline underline-offset-4 hover:text-accent-700"
       @if (request()->routeIs('legal.privacy')) aria-current="page" @endif>Datenschutz</a>
</nav>
