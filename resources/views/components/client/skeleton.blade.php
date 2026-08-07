{{-- Sprint 8.2.3 — Placeholder de carregamento (skeleton) --}}
@props([
    'variant' => 'text', // text|title|block|circle
    'width' => null,
    'height' => null,
])
@php
    $style = trim(($width ? "width:{$width};" : '').($height ? "height:{$height};" : ''));
@endphp
<span
    {{ $attributes->merge(['class' => 'client-skeleton client-skeleton--'.$variant, 'style' => $style ?: null]) }}
    aria-hidden="true"
></span>
