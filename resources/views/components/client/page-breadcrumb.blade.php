{{-- Sprint 8.2.2 — Trilha de navegação (breadcrumb) padrão --}}
@props([
    'items' => [],
])
<nav {{ $attributes->merge(['class' => 'client-breadcrumb']) }} aria-label="Trilha de navegação">
    @foreach($items as $item)
        @if(!$loop->first)
            <span class="client-breadcrumb__sep" aria-hidden="true">›</span>
        @endif
        @if(!empty($item['href']) && !$loop->last)
            <a class="client-breadcrumb__link" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
        @else
            <span class="client-breadcrumb__current" @if($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
