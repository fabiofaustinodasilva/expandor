{{-- Sprint 8.2.2 — Barra de filtros (GET) genérica --}}
@props([
    'action',
])
<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'client-filter-bar']) }}>
    {{ $slot }}
</form>
