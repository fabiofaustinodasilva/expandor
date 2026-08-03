@if(!empty($saasOnboardingBanner) && $saasOnboardingBanner->show)
    <div
        class="saas-onboarding-banner"
        data-saas-onboarding-banner="1"
        data-saas-percent="{{ $saasOnboardingBanner->percent }}"
        style="
            padding:.7rem 1.25rem;
            text-align:center;
            font-size:.92rem;
            font-weight:650;
            border-bottom:1px solid var(--border);
            background: color-mix(in srgb, var(--accent) 14%, var(--bg-elevated));
            color: var(--text);
        "
    >
        <span>Configuração da conta · {{ $saasOnboardingBanner->percent }}%</span>
        <span style="margin:0 .5rem; color:var(--muted);">—</span>
        <span style="font-weight:500;">Complete sua configuração para aproveitar todos os recursos.</span>
        @if($saasOnboardingBanner->continueUrl)
            <a href="{{ $saasOnboardingBanner->continueUrl }}"
               style="margin-left:.65rem; color:var(--accent); text-decoration:underline; font-weight:700;">
                Continuar
            </a>
        @endif
    </div>
@endif
