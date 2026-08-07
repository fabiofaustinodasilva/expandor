{{-- Sprint 8.2.2 — Cartão de seção com título e ação opcional --}}
@props([
    'title' => null,
    'description' => null,
])
<section {{ $attributes->merge(['class' => 'client-section-card']) }}>
    @if($title || $description || isset($actions))
        <div class="client-section-card__head">
            <div>
                @if($title)
                    <h2 class="client-section-card__title">{{ $title }}</h2>
                @endif
                @if($description)
                    <p class="client-section-card__desc">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="client-section-card__actions">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="client-section-card__body">
        {{ $slot }}
    </div>
</section>
