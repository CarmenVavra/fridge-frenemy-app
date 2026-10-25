@props(['route', 'icon', 'label'])
@php($active = request()->routeIs($route))
<a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
   @class([
       '-mt-[2px] flex flex-col items-start gap-1 border-t-4 px-0 pb-2 pl-[18px] pt-3 text-xs font-bold uppercase tracking-[.06em] no-underline',
       'border-accent text-ink' => $active,
       'border-transparent text-neutral-700 hover:text-ink' => ! $active,
   ])>
    <x-icon :name="$icon" :size="22" />
    {{ $label }}
</a>
