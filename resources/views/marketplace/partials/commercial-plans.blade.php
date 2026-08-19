@php
    $catalog = $premiumData['commercial_plans'] ?? [];
    $planCards = $catalog['plans'] ?? [];
    $enterprise = $catalog['enterprise'] ?? null;
    $planFeatures = $catalog['features'] ?? [];
    $planCtaHref = $catalog['cta_href'] ?? '#demo';
    $planCtaLabel = $catalog['cta_label'] ?? 'Agendar demonstração';
@endphp

@if(!empty($planCards))
    <div class="mkp-plans-grid mkp-plans-grid-commercial">
        @foreach($planCards as $planCard)
            <article class="mkp-plan {{ !empty($planCard['featured']) ? 'mkp-plan-featured' : '' }} mkp-fade" data-plan="{{ $planCard['key'] ?? '' }}">
                @if(!empty($planCard['badge']))
                    <span class="mkp-badge">{{ $planCard['badge'] }}</span>
                @endif
                <h3 style="margin:0;">{{ $planCard['name'] ?? '' }}</h3>
                <div class="mkp-plan-price">
                    {{ $planCard['price_label'] ?? '' }}
                    @if(!empty($planCard['period']))
                        <small>{{ $planCard['period'] }}</small>
                    @endif
                </div>
                @if(!empty($planCard['audience']))
                    <p class="mkp-plan-desc">{{ $planCard['audience'] }}</p>
                @endif
                @if(!empty($planFeatures))
                    <ul class="mkp-plan-features">
                        @foreach($planFeatures as $featureLabel)
                            <li>{{ $featureLabel }}</li>
                        @endforeach
                    </ul>
                @endif
                <a class="mkp-btn mkp-btn-primary" href="{{ $planCtaHref }}" data-mkp-event="marketplace.demo_clicked">
                    {{ $planCard['cta_label'] ?? $planCtaLabel }}
                </a>
            </article>
        @endforeach
    </div>
@endif

@if(!empty($enterprise))
    <article class="mkp-plan mkp-plan-enterprise mkp-fade" data-plan="enterprise">
        <div>
            <h3 style="margin:0;">{{ $enterprise['name'] ?? 'Enterprise' }}</h3>
            <div class="mkp-plan-price">{{ $enterprise['price_label'] ?? 'Sob consulta' }}</div>
            @if(!empty($enterprise['audience']))
                <p class="mkp-plan-desc" style="margin-bottom:0;">{{ $enterprise['audience'] }}</p>
            @endif
        </div>
        <a class="mkp-btn mkp-btn-outline" href="{{ $planCtaHref }}" data-mkp-event="marketplace.demo_clicked">
            {{ $enterprise['cta_label'] ?? $planCtaLabel }}
        </a>
    </article>
@endif

@if(!empty($catalog['note']))
    <p class="mkp-plan-note">{{ $catalog['note'] }}</p>
@endif
