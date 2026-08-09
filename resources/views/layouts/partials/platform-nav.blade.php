{{-- Sprint 8.1.2 — Navegação Platform (PT-BR, grupos recolhíveis, ícones) --}}
@php
    $navOpen = function (string ...$patterns): bool {
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    };
@endphp
<nav aria-label="Menu da plataforma" class="ent-nav">
    <div class="nav-section {{ $navOpen('platform.dashboard', 'platform.activation.*', 'platform.saas.intelligence*', 'platform.profile.*') ? 'is-open' : '' }}" data-nav-section>
        <button type="button" class="nav-section-toggle" aria-expanded="{{ $navOpen('platform.dashboard', 'platform.activation.*', 'platform.saas.intelligence*', 'platform.profile.*') ? 'true' : 'false' }}">
            <span class="nav-section-left">
                <span class="nav-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 13h6V4H4v9zm10 7h6V4h-6v16zM4 20h6v-5H4v5z"/></svg>
                </span>
                Visão geral
            </span>
            <span class="nav-chevron" aria-hidden="true"></span>
        </button>
        <div class="nav-section-body">
            <a class="nav-link {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}" href="{{ route('platform.dashboard') }}">Painel</a>
            <a class="nav-link {{ request()->routeIs('platform.activation.*') ? 'active' : '' }}" href="{{ route('platform.activation.index') }}">Saúde da operação</a>
            <a class="nav-link {{ request()->routeIs('platform.saas.intelligence*') ? 'active' : '' }}" href="{{ route('platform.saas.intelligence') }}">Inteligência comercial</a>
            <a class="nav-link {{ request()->routeIs('platform.profile.*') ? 'active' : '' }}" href="{{ route('platform.profile.edit') }}">Meu perfil</a>
        </div>
    </div>

    @can('platform.manageCompanies')
        <div class="nav-section {{ $navOpen('platform.companies.*', 'platform.billing.*', 'platform.plans.*') ? 'is-open' : '' }}" data-nav-section>
            <button type="button" class="nav-section-toggle" aria-expanded="{{ $navOpen('platform.companies.*', 'platform.billing.*', 'platform.plans.*') ? 'true' : 'false' }}">
                <span class="nav-section-left">
                    <span class="nav-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                    </span>
                    Empresas
                </span>
                <span class="nav-chevron" aria-hidden="true"></span>
            </button>
            <div class="nav-section-body">
                <a class="nav-link {{ request()->routeIs('platform.companies.*') ? 'active' : '' }}" href="{{ route('platform.companies.index') }}">Clientes</a>
                @can('platform.managePlans')
                    <a class="nav-link {{ request()->routeIs('platform.plans.*') ? 'active' : '' }}" href="{{ route('platform.plans.index') }}">Planos</a>
                @endcan
                <a class="nav-link {{ request()->routeIs('platform.billing.*') ? 'active' : '' }}" href="{{ route('platform.billing.index') }}">Assinaturas e cobrança</a>
            </div>
        </div>
    @endcan

    @can('marketplace.manage')
        <div class="nav-section {{ $navOpen('platform.marketplace.settings.*', 'platform.marketplace.sections.*', 'platform.marketplace.media.*', 'platform.marketplace.mercadopago*', 'platform.marketplace.preview', 'platform.branding.*') ? 'is-open' : '' }}" data-nav-section>
            <button type="button" class="nav-section-toggle" aria-expanded="{{ $navOpen('platform.marketplace.settings.*', 'platform.marketplace.sections.*', 'platform.marketplace.media.*', 'platform.marketplace.mercadopago*', 'platform.marketplace.preview', 'platform.branding.*') ? 'true' : 'false' }}">
                <span class="nav-section-left">
                    <span class="nav-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </span>
                    Configurações
                </span>
                <span class="nav-chevron" aria-hidden="true"></span>
            </button>
            <div class="nav-section-body">
                @can('platform.manageBranding')
                    <a class="nav-link {{ request()->routeIs('platform.branding.*') ? 'active' : '' }}" href="{{ route('platform.branding.edit') }}">Identidade</a>
                @endcan
                <a class="nav-link {{ request()->routeIs('platform.marketplace.settings.*') && !request()->routeIs('platform.marketplace.mercadopago*') ? 'active' : '' }}" href="{{ route('platform.marketplace.settings.edit') }}">Site público</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.sections.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.sections.index') }}">Seções do site</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.media.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.media.index') }}">Conteúdo e mídias</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.mercadopago*') ? 'active' : '' }}" href="{{ route('platform.marketplace.mercadopago.edit') }}">Mercado Pago</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.preview') ? 'active' : '' }}" href="{{ route('platform.marketplace.preview') }}" target="_blank" rel="noopener">Visualizar site</a>
            </div>
        </div>

        <div class="nav-section {{ $navOpen('platform.marketplace.leads.*', 'platform.marketplace.analytics', 'platform.marketplace.intelligence', 'platform.marketplace.pipeline.*', 'platform.marketplace.segments.*', 'platform.marketplace.cases.*', 'platform.marketplace.campaigns.*') ? 'is-open' : '' }}" data-nav-section>
            <button type="button" class="nav-section-toggle" aria-expanded="{{ $navOpen('platform.marketplace.leads.*', 'platform.marketplace.analytics', 'platform.marketplace.intelligence', 'platform.marketplace.pipeline.*', 'platform.marketplace.segments.*', 'platform.marketplace.cases.*', 'platform.marketplace.campaigns.*') ? 'true' : 'false' }}">
                <span class="nav-section-left">
                    <span class="nav-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg>
                    </span>
                    Aquisição
                </span>
                <span class="nav-chevron" aria-hidden="true"></span>
            </button>
            <div class="nav-section-body">
                <a class="nav-link {{ request()->routeIs('platform.marketplace.leads.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.leads.index') }}">Leads</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.analytics') ? 'active' : '' }}" href="{{ route('platform.marketplace.analytics') }}">Análises</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.intelligence') ? 'active' : '' }}" href="{{ route('platform.marketplace.intelligence') }}">Inteligência</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.pipeline.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.pipeline.index') }}">Funil comercial</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.segments.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.segments.index') }}">Segmentos</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.cases.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.cases.index') }}">Cases de sucesso</a>
                <a class="nav-link {{ request()->routeIs('platform.marketplace.campaigns.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.campaigns.index') }}">Campanhas de mídia</a>
            </div>
        </div>
    @else
        @can('platform.manageBranding')
            <div class="nav-section is-open" data-nav-section>
                <button type="button" class="nav-section-toggle" aria-expanded="true">
                    <span class="nav-section-left">
                        <span class="nav-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </span>
                        Configurações
                    </span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <div class="nav-section-body">
                    <a class="nav-link {{ request()->routeIs('platform.branding.*') ? 'active' : '' }}" href="{{ route('platform.branding.edit') }}">Identidade</a>
                </div>
            </div>
        @endcan
    @endcan

    @canany(['platform.manageFeatureFlags', 'platform.viewHealth', 'platform.managePlans'])
        <div class="nav-section {{ $navOpen('platform.flags.*', 'platform.health.*', 'platform.integrations.*') ? 'is-open' : '' }}" data-nav-section>
            <button type="button" class="nav-section-toggle" aria-expanded="{{ $navOpen('platform.flags.*', 'platform.health.*', 'platform.integrations.*') ? 'true' : 'false' }}">
                <span class="nav-section-left">
                    <span class="nav-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </span>
                    Sistema
                </span>
                <span class="nav-chevron" aria-hidden="true"></span>
            </button>
            <div class="nav-section-body">
                @can('platform.managePlans')
                    <a class="nav-link {{ request()->routeIs('platform.integrations.*') ? 'active' : '' }}" href="{{ route('platform.integrations.index') }}">Integrações</a>
                @endcan
                @can('platform.manageFeatureFlags')
                    <a class="nav-link {{ request()->routeIs('platform.flags.*') ? 'active' : '' }}" href="{{ route('platform.flags.index') }}">Recursos experimentais</a>
                @endcan
                @can('platform.viewHealth')
                    <a class="nav-link {{ request()->routeIs('platform.health.*') ? 'active' : '' }}" href="{{ route('platform.health.index') }}">Indicador de saúde</a>
                @endcan
            </div>
        </div>
    @endcanany
</nav>
<script>
(function () {
    document.querySelectorAll('[data-nav-section]').forEach(function (section) {
        var btn = section.querySelector('.nav-section-toggle');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var open = section.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
})();
</script>
