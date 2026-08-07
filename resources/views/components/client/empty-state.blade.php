{{-- Sprint 8.2.2 — Estado vazio padrão --}}
@props([
    'title' => 'Nada por aqui ainda',
    'description' => null,
    'actionHref' => null,
    'actionLabel' => null,
    'icon' => 'inbox',
])
<div {{ $attributes->merge(['class' => 'client-empty-state']) }} role="status">
    <div class="client-empty-state__icon" aria-hidden="true"><i data-lucide="{{ $icon }}" class="w-6 h-6"></i></div>
    <p class="client-empty-state__title">{{ $title }}</p>
    @if($description)
        <p class="client-empty-state__desc">{{ $description }}</p>
    @endif
    {{ $slot }}
    @if($actionHref && $actionLabel)
        <a class="btn btn-primary" href="{{ $actionHref }}">{{ $actionLabel }}</a>
    @endif
</div>
