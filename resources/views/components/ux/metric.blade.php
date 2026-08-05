@props([
    'label',
    'hint' => null,
    'value' => null,
])
<div {{ $attributes->merge(['class' => 'metric-card card']) }}>
    <div class="metric-card__label">{{ $label }}</div>
    <div class="metric-card__value">{{ $value ?? $slot }}</div>
    @if($hint)
        <div class="metric-card__hint">{{ $hint }}</div>
    @endif
</div>
