@props([
    'lines' => 3,
    'title' => true,
    'card' => false,
])

@if($card)
    <div {{ $attributes->merge(['class' => 'ux-skeleton ux-skeleton--card']) }} aria-hidden="true"></div>
@else
    <div {{ $attributes->merge(['class' => 'ux-skeleton-block']) }} aria-busy="true" aria-label="Carregando">
        @if($title)
            <div class="ux-skeleton ux-skeleton--title"></div>
        @endif
        @for($i = 0; $i < (int) $lines; $i++)
            <div class="ux-skeleton ux-skeleton--line"></div>
        @endfor
    </div>
@endif
