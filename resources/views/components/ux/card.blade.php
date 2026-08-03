@props([
    'title' => null,
])
<section {{ $attributes->merge(['class' => 'card']) }}>
    @if($title)
        <h2 style="margin:0 0 .85rem; font-size:1rem;">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
