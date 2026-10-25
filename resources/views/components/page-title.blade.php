@props(['kicker', 'title'])
<header {{ $attributes->merge(['class' => 'px-5 pb-4 pt-3']) }}>
    <p class="ff-kicker text-accent-700">{{ $kicker }}</p>
    <h1 class="ff-display mt-1">{{ $title }}</h1>
</header>
