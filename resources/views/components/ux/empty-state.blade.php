@props([
    'title' => 'Nada por aqui ainda',
    'description' => 'Quando houver dados, eles aparecerão nesta área.',
    'actionHref' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'ux-empty']) }} role="status">
    <p class="ux-empty__title">{{ $title }}</p>
    @if($description)
        <p class="ux-empty__desc">{{ $description }}</p>
    @endif
    {{ $slot }}
    @if($actionHref && $actionLabel)
        <a class="btn btn-primary" href="{{ $actionHref }}" style="width:auto; display:inline-flex;">{{ $actionLabel }}</a>
    @endif
</div>
