{{-- Sprint 8.2.3 — Indicador de carregamento inline (spinner + aria-busy) --}}
@props([
    'label' => 'Carregando…',
])
<div {{ $attributes->merge(['class' => 'client-loading']) }} role="status" aria-busy="true" aria-live="polite">
    <span class="client-loading__spinner" aria-hidden="true"></span>
    <span>{{ $label }}</span>
</div>
