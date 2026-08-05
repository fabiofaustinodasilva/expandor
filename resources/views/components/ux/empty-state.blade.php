@props([
    'title' => 'Nada por aqui ainda',
    'description' => 'Quando houver dados, eles aparecerão nesta área.',
    'actionHref' => null,
    'actionLabel' => null,
    'icon' => '◇',
])

<div {{ $attributes->merge(['class' => 'ux-empty']) }} role="status">
    <div class="ux-empty__icon" aria-hidden="true">{{ $icon }}</div>
    <p class="ux-empty__title">{{ $title }}</p>
    @if($description)
        <p class="ux-empty__desc">{{ $description }}</p>
    @endif
    {{ $slot }}
    @if($actionHref && $actionLabel)
        <a class="btn btn-primary" href="{{ $actionHref }}">{{ $actionLabel }}</a>
    @endif
</div>
