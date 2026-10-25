@props(['operator'])
{{-- Name and address of whoever runs this installation (config/legal.php). Missing values are flagged so they get noticed. --}}
<address {{ $attributes->merge(['class' => 'not-italic leading-relaxed']) }}>
    <span class="block font-bold">{{ $operator['name'] ?: '[Name fehlt: LEGAL_NAME]' }}</span>
    @if (filled($operator['street']))
        <span class="block">{{ $operator['street'] }}</span>
    @endif
    <span class="block">{{ $operator['city'] ?: '[Ort fehlt: LEGAL_CITY]' }}</span>
    @if (filled($operator['country']))
        <span class="block">{{ $operator['country'] }}</span>
    @endif
    <span class="mt-2 block">
        E-Mail:
        @if (filled($operator['email']))
            <a href="mailto:{{ $operator['email'] }}" class="font-semibold text-accent-700 underline underline-offset-4 hover:text-accent-600">{{ $operator['email'] }}</a>
        @else
            [E-Mail fehlt: LEGAL_EMAIL]
        @endif
    </span>
</address>
