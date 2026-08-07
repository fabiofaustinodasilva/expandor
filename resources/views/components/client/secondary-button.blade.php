{{-- Sprint 8.2.2 — Botão secundário (ghost) --}}
@props([
    'href' => null,
    'type' => 'button',
])
@if($href)
    <a {{ $attributes->merge(['class' => 'btn btn-ghost client-btn', 'href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => 'btn btn-ghost client-btn', 'type' => $type]) }}>{{ $slot }}</button>
@endif
