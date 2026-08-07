{{-- Sprint 8.2.2 — Barra de busca simples (GET) --}}
@props([
    'action',
    'name' => 'q',
    'value' => '',
    'placeholder' => 'Buscar…',
    'clearHref' => null,
])
<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'client-search-bar']) }}>
    <input
        type="search"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="form-control client-search-bar__input"
    >
    <button type="submit" class="btn btn-primary">Buscar</button>
    @if($clearHref && $value !== '')
        <a class="btn btn-ghost" href="{{ $clearHref }}">Limpar</a>
    @endif
</form>
