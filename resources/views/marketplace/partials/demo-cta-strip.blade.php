@php $cta = $premiumData['demo_cta'] ?? []; @endphp
@if(!empty($cta))
    <div class="mkp-demo-strip">
        @if(!empty($cta['eyebrow']))
            <span class="mkp-eyebrow">{{ $cta['eyebrow'] }}</span>
        @endif
        @if(!empty($cta['title']))
            <p class="mkp-demo-strip-title">{{ $cta['title'] }}</p>
        @endif
        <a class="mkp-btn mkp-btn-primary" href="{{ $cta['href'] ?? '#demo' }}" data-mkp-event="marketplace.demo_clicked">
            {{ $cta['button'] ?? 'Agendar demonstração' }}
        </a>
    </div>
@endif
