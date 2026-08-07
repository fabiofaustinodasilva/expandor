{{-- Sprint 8.2.2 — Botão primário --}}
@props([
    'href' => null,
    'type' => 'button',
])
@if($href)
    <a {{ $attributes->merge(['class' => 'btn btn-primary client-btn', 'href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => 'btn btn-primary client-btn', 'type' => $type]) }}>{{ $slot }}</button>
@endif
