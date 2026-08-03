@props([
    'tone' => 'neutral',
])
@php
    $extra = match ($tone) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'badge-danger',
        'primary' => 'badge-primary',
        default => '',
    };
@endphp
<span {{ $attributes->merge(['class' => trim('badge '.$extra)]) }}>{{ $slot }}</span>
