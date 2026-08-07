{{-- Sprint 8.2.2 — Botão de ação destrutiva --}}
@props([
    'href' => null,
    'type' => 'button',
    'confirm' => null,
])
@if($href)
    <a {{ $attributes->merge(['class' => 'btn client-btn-danger client-btn', 'href' => $href, 'onclick' => $confirm ? "return confirm('{$confirm}')" : null]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => 'btn client-btn-danger client-btn', 'type' => $type, 'onclick' => $confirm ? "return confirm('{$confirm}')" : null]) }}>{{ $slot }}</button>
@endif
