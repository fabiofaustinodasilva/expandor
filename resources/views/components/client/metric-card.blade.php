{{-- Sprint 8.2.2 — Cartão de métrica compacto --}}
@props([
    'label',
    'value' => null,
    'hint' => null,
    'icon' => null,
])
<div {{ $attributes->merge(['class' => 'client-metric-card']) }}>
    @if($icon)
        <div class="client-metric-card__icon" aria-hidden="true"><i data-lucide="{{ $icon }}" class="w-5 h-5"></i></div>
    @endif
    <div class="client-metric-card__label">{{ $label }}</div>
    <div class="client-metric-card__value">{{ $value ?? $slot }}</div>
    @if($hint)
        <div class="client-metric-card__hint">{{ $hint }}</div>
    @endif
</div>
