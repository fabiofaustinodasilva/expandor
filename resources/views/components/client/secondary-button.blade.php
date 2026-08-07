{{-- Sprint 8.2.2 — Botão secundário (ghost) --}}
@props([
    'href' => null,
    'type' => 'button',
])
@if($href)
    <a {{ $attributes->merge(['class' => 'btn btn-ghost', 'href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => 'btn btn-ghost', 'type' => $type]) }}>{{ $slot }}</button>
@endif
