{{-- Classes UX padronizadas: .btn .btn-primary .btn-secondary .btn-ghost .btn-outline .btn-danger .btn-success --}}
@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'loading' => false,
])
@php
    $map = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        'outline' => 'btn-outline',
        'danger' => 'btn-danger',
        'success' => 'btn-success',
    ];
    $class = 'btn '.($map[$variant] ?? 'btn-primary').($loading ? ' is-loading' : '');
@endphp
@if($href)
    <a {{ $attributes->merge(['class' => $class, 'href' => $href, 'aria-disabled' => $loading ? 'true' : null]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $class, 'type' => $type, 'disabled' => $loading]) }}>{{ $slot }}</button>
@endif
