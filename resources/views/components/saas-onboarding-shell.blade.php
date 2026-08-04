@php
    /** @var \App\Domains\Onboarding\DTOs\SaasOnboardingProgress|null $progress */
    $progress = $progress ?? null;
    $stepKey = $stepKey ?? 'index';
@endphp

<style>
    .saas-onb-shell { max-width: 720px; margin: 0 auto; animation: saasOnbIn .35s ease; }
    .saas-onb-top {
        position: sticky; top: 0; z-index: 5;
        margin: -0.25rem 0 1.25rem;
        padding: .85rem 1rem;
        border: 1px solid var(--border);
        border-radius: 14px;
        background: color-mix(in srgb, var(--bg-elevated) 92%, transparent);
        backdrop-filter: blur(8px);
    }
    .saas-onb-bar { background: var(--bg-soft); border-radius: 999px; overflow: hidden; height: 10px; }
    .saas-onb-bar > span {
        display:block; height:100%; background: linear-gradient(90deg, var(--accent), color-mix(in srgb, var(--accent) 60%, #fff));
        transition: width .4s ease;
    }
    .saas-onb-card {
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.25rem 1.35rem;
        background: var(--bg-elevated);
        box-shadow: 0 10px 30px color-mix(in srgb, #000 12%, transparent);
    }
    .saas-onb-actions { display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1.25rem; }
    @keyframes saasOnbIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="saas-onb-shell" data-saas-onboarding="1" data-saas-step="{{ $stepKey }}">
    @if($progress)
        <div class="saas-onb-top" data-saas-onboarding-progress="1" data-saas-percent="{{ $progress->percent }}">
            <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:.55rem;">
                <strong>Configuração da conta</strong>
                <span class="header-meta">{{ $progress->percent }}% concluído</span>
            </div>
            <div class="saas-onb-bar" aria-hidden="true">
                <span style="width:{{ $progress->percent }}%;"></span>
            </div>
        </div>
    @endif

    {{ $slot }}
</div>
