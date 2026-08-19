@php $journeySteps = $premiumData['journey'] ?? []; @endphp
@if(!empty($journeySteps))
    <ol class="mkp-journey">
        @foreach($journeySteps as $step)
            <li class="mkp-journey-step mkp-fade">
                <div class="mkp-journey-meta">
                    <span class="mkp-step-num">{{ $step['step'] ?? $loop->iteration }}</span>
                    <h3>{{ $step['title'] ?? '' }}</h3>
                    <p>{{ $step['copy'] ?? '' }}</p>
                </div>
                <figure class="mkp-journey-shot">
                    @include('marketplace.partials.product-shot', [
                        'shot' => $step['shot'],
                        'alt' => $step['alt'] ?? ($step['title'] ?? 'Expandor'),
                        'width' => $loop->iteration === 1 ? 1280 : 780,
                        'height' => $loop->iteration === 1 ? 800 : 1688,
                    ])
                </figure>
            </li>
        @endforeach
    </ol>
@endif
