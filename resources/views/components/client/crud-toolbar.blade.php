{{-- Sprint 8.2.3 — Barra de ferramentas padrão das telas de listagem (CRUD) --}}
{{-- Slots: search, filters, actions (todos opcionais) --}}
<div {{ $attributes->merge(['class' => 'client-crud-toolbar']) }}>
    @isset($search)
        <div class="client-crud-toolbar__search">{{ $search }}</div>
    @endisset
    @isset($filters)
        <div class="client-crud-toolbar__filters">{{ $filters }}</div>
    @endisset
    @isset($actions)
        <div class="client-crud-toolbar__actions">{{ $actions }}</div>
    @endisset
    {{ $slot }}
</div>
