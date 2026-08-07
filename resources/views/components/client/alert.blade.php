{{-- Sprint 8.2.3 — Alerta com tonalidade semântica (success|error|warning|info) --}}
@props([
    'type' => 'info',
])
@php
    $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    $icon = match ($type) {
        'success' => 'check-circle',
        'error' => 'alert-circle',
        'warning' => 'alert-triangle',
        default => 'info',
    };
@endphp
<div {{ $attributes->merge(['class' => 'client-alert client-alert--'.$type]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">
    <span class="client-alert__icon" aria-hidden="true"><i data-lucide="{{ $icon }}" class="w-4 h-4"></i></span>
    <div class="client-alert__body">{{ $slot }}</div>
</div>
