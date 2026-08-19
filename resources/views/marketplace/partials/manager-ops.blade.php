@php $managerBlock = $premiumData['manager'] ?? []; @endphp
@if(!empty($managerBlock))
    <section id="gestor" class="mkp-section mkp-section-alt mkp-fade">
        <div class="mkp-container">
            <div class="mkp-section-head">
                @if(!empty($managerBlock['subtitle']))
                    <span class="mkp-eyebrow">{{ $managerBlock['subtitle'] }}</span>
                @endif
                <h2 class="mkp-title">{{ $managerBlock['title'] ?? '' }}</h2>
                @if(!empty($managerBlock['description']))
                    <p class="mkp-subtitle">{{ $managerBlock['description'] }}</p>
                @endif
            </div>
            <div class="mkp-manager-grid">
                @foreach(($managerBlock['shots'] ?? []) as $shot)
                    <figure class="mkp-product-frame mkp-fade">
                        @include('marketplace.partials.product-shot', [
                            'shot' => $shot['shot'],
                            'alt' => $shot['alt'] ?? 'Visão do gestor no Expandor',
                            'width' => 1280,
                            'height' => 800,
                        ])
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
