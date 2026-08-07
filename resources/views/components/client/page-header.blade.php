{{-- Sprint 8.2.2 — Cabeçalho padrão das telas da área do cliente --}}
@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])
<header {{ $attributes->merge(['class' => 'client-page-header']) }}>
    <div class="client-page-header__main">
        @if($eyebrow)
            <p class="client-page-header__eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="client-page-header__title">{{ $title }}</h1>
        @if($description)
            <p class="client-page-header__desc">{{ $description }}</p>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="client-page-header__actions">
            {{ $slot }}
        </div>
    @endif
</header>
