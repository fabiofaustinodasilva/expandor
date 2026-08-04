@props(['settings'])

@php
    $links = $settings->socialNetworks();
@endphp

@if(count($links) > 0)
    <div class="mkp-social" {{ $attributes }}>
        @foreach($links as $link)
            <a
                href="{{ $link['url'] }}"
                target="_blank"
                rel="noopener noreferrer"
                @if($link['event']) data-mkp-event="{{ $link['event'] }}" @endif
            >{{ $link['label'] }}</a>
        @endforeach
    </div>
@endif
