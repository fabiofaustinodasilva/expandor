{{-- Sprint 8.2.3 — Wrapper estilizado para paginação --}}
{{-- Passe :paginator="$items" para renderizar $items->links(), ou use o slot padrão. --}}
@props([
    'paginator' => null,
])
<div {{ $attributes->merge(['class' => 'client-pagination']) }}>
    @if($paginator)
        {{ $paginator->links() }}
    @else
        {{ $slot }}
    @endif
</div>
