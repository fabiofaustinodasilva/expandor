{{-- Sprint 8.2.3 — Tooltip acessível (hover + foco por teclado) --}}
@props([
    'text' => '',
])
<span {{ $attributes->merge(['class' => 'client-tooltip']) }} tabindex="0">
    <span class="client-tooltip__trigger">{{ $slot }}</span>
    <span class="client-tooltip__bubble" role="tooltip">{{ $text }}</span>
</span>
