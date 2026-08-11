{{-- Sprint 8.2.2 — Badge de status genérico (tonalidade configurável) --}}
@props([
    'tone' => 'neutral',
])
@php
    $extra = match ($tone) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'badge-danger',
        'info' => 'badge-info',
        'primary' => 'badge-primary',
        default => '',
    };
@endphp
<span {{ $attributes->merge(['class' => trim('badge '.$extra)]) }}>{{ $slot }}</span>
