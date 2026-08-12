{{-- Local seller bundle (no CDN). filemtime busts production cache after rebuild. --}}
@php
    $sellerCss = public_path('vendor/expandor/seller-app.css');
    $sellerJs = public_path('vendor/expandor/seller-app.js');
    $sellerCssV = is_file($sellerCss) ? filemtime($sellerCss) : 1;
    $sellerJsV = is_file($sellerJs) ? filemtime($sellerJs) : 1;
@endphp
@if($includeCss ?? true)
    <link rel="stylesheet" href="{{ asset('vendor/expandor/seller-app.css') }}?v={{ $sellerCssV }}">
@endif
<script src="{{ asset('vendor/expandor/seller-app.js') }}?v={{ $sellerJsV }}"></script>
