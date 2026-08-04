@props(['settings'])

@php
    $links = [];
    if ($settings->instagram_enabled && filled($settings->instagram_url)) {
        $links[] = ['label' => 'Instagram', 'url' => $settings->instagram_url, 'event' => 'marketplace.instagram_clicked'];
    }
    if ($settings->facebook_enabled && filled($settings->facebook_url)) {
        $links[] = ['label' => 'Facebook', 'url' => $settings->facebook_url, 'event' => null];
    }
    if ($settings->youtube_enabled && filled($settings->youtube_url)) {
        $links[] = ['label' => 'YouTube', 'url' => $settings->youtube_url, 'event' => null];
    }
    if ($settings->linkedin_enabled && filled($settings->linkedin_url)) {
        $links[] = ['label' => 'LinkedIn', 'url' => $settings->linkedin_url, 'event' => null];
    }
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
