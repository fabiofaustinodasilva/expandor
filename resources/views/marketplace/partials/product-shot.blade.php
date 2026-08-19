@props([
    'shot',
    'alt',
    'width' => 1280,
    'height' => 800,
    'lazy' => true,
    'priority' => false,
    'class' => '',
])

@php
    $webp = asset('images/marketplace/product/'.$shot.'.webp');
    $png = asset('images/marketplace/product/'.$shot.'.png');
@endphp
<picture>
    <source type="image/webp" srcset="{{ $webp }}">
    <img src="{{ $png }}"
         alt="{{ $alt }}"
         width="{{ $width }}"
         height="{{ $height }}"
         class="{{ $class }}"
         decoding="async"
         @if($priority) fetchpriority="high" @elseif($lazy) loading="lazy" @endif>
</picture>
