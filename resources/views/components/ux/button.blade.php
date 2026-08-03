{{-- Classes UX padronizadas: .btn .btn-primary .btn-ghost .card .badge .alert .modal-shell --}}
@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
])
@php
    $class = 'btn '.($variant === 'ghost' ? 'btn-ghost' : 'btn-primary');
@endphp
@if($href)
    <a {{ $attributes->merge(['class' => $class, 'href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $class, 'type' => $type]) }}>{{ $slot }}</button>
@endif
