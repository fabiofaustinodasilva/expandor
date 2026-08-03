@if(!empty($trialBanner) && $trialBanner->show)
    <div
        class="trial-banner trial-banner--{{ $trialBanner->variant }}"
        data-trial-banner="1"
        data-trial-variant="{{ $trialBanner->variant }}"
        style="
            padding:.7rem 1.25rem;
            text-align:center;
            font-size:.92rem;
            font-weight:650;
            border-bottom:1px solid var(--border);
            background: {{ $trialBanner->variant === 'expired'
                ? 'color-mix(in srgb, var(--accent-2, #EF4444) 22%, var(--bg-elevated))'
                : ($trialBanner->variant === 'tomorrow'
                    ? 'color-mix(in srgb, var(--warning, #F59E0B) 22%, var(--bg-elevated))'
                    : 'color-mix(in srgb, var(--accent) 18%, var(--bg-elevated))') }};
            color: var(--text);
        "
    >
        <span>{{ $trialBanner->message }}</span>
        @if($trialBanner->convertUrl)
            <a href="{{ $trialBanner->isExpired ? route('trial.conversion') : $trialBanner->convertUrl }}"
               style="margin-left:.65rem; color:var(--accent); text-decoration:underline; font-weight:700;">
                {{ $trialBanner->isExpired ? 'Escolher plano' : 'Ver assinatura' }}
            </a>
        @elseif($trialBanner->isExpired)
            <a href="{{ route('trial.conversion') }}"
               style="margin-left:.65rem; color:var(--accent); text-decoration:underline; font-weight:700;">
                Escolher plano
            </a>
        @endif
    </div>
@endif
