@props(['mood' => 'meh', 'size' => 72])
@php($face = \App\Support\FridgeMood::face($mood))
{{-- Static avatar (Bilanz). The dashboard binds the same shape via Alpine. --}}
<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 100 100" fill="none" stroke="currentColor"
     stroke-width="3.5" stroke-linecap="round" role="img" aria-label="Kühlschrank-Avatar">
    <rect x="20" y="6" width="60" height="88" fill="{{ $face['fill'] }}"></rect>
    <path d="M20 34H80"></path><path d="M70 16V26M70 44V62"></path>
    <circle cx="40" cy="58" r="4" fill="currentColor" stroke="none"></circle>
    <circle cx="58" cy="58" r="4" fill="currentColor" stroke="none"></circle>
    <path d="{{ $face['brows'] }}"></path><path d="{{ $face['mouth'] }}"></path>
</svg>
