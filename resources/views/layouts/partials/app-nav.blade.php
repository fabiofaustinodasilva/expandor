{{-- Sprint 8.1.2 — Navegação empresa (PT-BR, grupos) --}}
@php
    $open = function (string ...$patterns): bool {
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    };
@endphp
<nav aria-label="Menu principal" class="ent-nav">
    <div class="nav-section {{ $open('dashboard', 'sales-app.*', 'map.*') ? 'is-open' : 'is-open' }}" data-nav-section>
        <button type="button" class="nav-section-toggle" aria-expanded="true">
            <span class="nav-section-left">
                <span class="nav-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 13h6V4H4v9zm10 7h6V4h-6v16zM4 20h6v-5H4v5z"/></svg></span>
                Início
            </span>
            <span class="nav-chevron" aria-hidden="true"></span>
        </button>
        <div class="nav-section-body">
            @if($authUser?->hasPermission('sales_app.access'))
                <a class="nav-link {{ request()->routeIs('sales-app.*') ? 'active' : '' }}" href="{{ route('sales-app.dashboard') }}">App de campo</a>
            @endif
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Painel</a>
            @if($authUser?->hasPermission('maps.view'))
                <a class="nav-link {{ request()->routeIs('map.*') ? 'active' : '' }}" href="{{ route('map.index') }}">Mapa</a>
            @endif
        </div>
    </div>

    <div class="nav-section {{ $open('crm.*', 'campaigns.*', 'follow-ups.*', 'visits.*', 'campaigns.visits.*', 'commissions.*', 'properties.*', 'residents.*', 'addresses.*', 'cities.*', 'sectors.*') ? 'is-open' : '' }}" data-nav-section>
        <button type="button" class="nav-section-toggle" aria-expanded="{{ $open('crm.*', 'campaigns.*', 'follow-ups.*', 'visits.*', 'campaigns.visits.*', 'commissions.*', 'properties.*', 'residents.*', 'addresses.*', 'cities.*', 'sectors.*') ? 'true' : 'false' }}">
            <span class="nav-section-left">
                <span class="nav-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                Comercial
            </span>
            <span class="nav-chevron" aria-hidden="true"></span>
        </button>
        <div class="nav-section-body">
            @if($authUser?->hasPermission('crm.view'))
                <a class="nav-link {{ request()->routeIs('crm.*') ? 'active' : '' }}" href="{{ route('crm.dashboard') }}">Clientes e oportunidades</a>
            @endif
            @if($authUser?->hasPermission('campaigns.view'))
                <a class="nav-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}" href="{{ route('campaigns.index') }}">Campanhas</a>
            @endif
            @if($authUser?->hasPermission('visits.view'))
                <a class="nav-link {{ request()->routeIs('follow-ups.*', 'visits.*', 'campaigns.visits.*') ? 'active' : '' }}" href="{{ route('follow-ups.index') }}">Agenda</a>
            @endif
            @if($authUser?->hasPermission('properties.view'))
                <a class="nav-link {{ request()->routeIs('properties.*', 'residents.*') ? 'active' : '' }}" href="{{ route('properties.index') }}">Clientes / Pontos</a>
                <a class="nav-link {{ request()->routeIs('addresses.*') ? 'active' : '' }}" href="{{ route('addresses.index') }}">Endereços</a>
            @endif
            @if($authUser?->hasPermission('cities.view'))
                <a class="nav-link {{ request()->routeIs('cities.*') ? 'active' : '' }}" href="{{ route('cities.index') }}">Cidades</a>
            @endif
            @if($authUser?->hasPermission('sectors.view'))
                <a class="nav-link {{ request()->routeIs('sectors.*') ? 'active' : '' }}" href="{{ route('sectors.index') }}">Setores</a>
            @endif
            @if($authUser?->hasPermission('commissions.manage'))
                <a class="nav-link {{ request()->routeIs('commissions.index') ? 'active' : '' }}" href="{{ route('commissions.index') }}">Comissões</a>
                <a class="nav-link {{ request()->routeIs('commissions.products.*') ? 'active' : '' }}" href="{{ route('commissions.products.index') }}">Produtos / Estoque</a>
            @elseif($authUser?->hasPermission('commissions.view_self'))
                <a class="nav-link {{ request()->routeIs('commissions.index') ? 'active' : '' }}" href="{{ route('commissions.index') }}">Minha comissão</a>
            @endif
        </div>
    </div>

    <div class="nav-section {{ $open('users.*', 'operations.team', 'company.show', 'company.edit', 'company.branding.*', 'setup.*', 'company.plan.*', 'company.subscription.*') ? 'is-open' : '' }}" data-nav-section>
        <button type="button" class="nav-section-toggle" aria-expanded="{{ $open('users.*', 'operations.team', 'company.show', 'company.edit', 'company.branding.*', 'setup.*', 'company.plan.*', 'company.subscription.*') ? 'true' : 'false' }}">
            <span class="nav-section-left">
                <span class="nav-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg></span>
                Empresa
            </span>
            <span class="nav-chevron" aria-hidden="true"></span>
        </button>
        <div class="nav-section-body">
            @if($authUser?->role?->slug === \App\Domains\Company\Models\Role::ADMINISTRATOR)
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Usuários</a>
            @endif
            @if($authUser?->hasPermission('users.view') || $authUser?->hasPermission('users.manage'))
                <a class="nav-link {{ request()->routeIs('operations.team') ? 'active' : '' }}" href="{{ route('operations.team') }}">Equipe</a>
            @endif
            @if($authUser?->hasPermission('company.manage') && $company)
                <a class="nav-link {{ request()->routeIs('company.show', 'company.edit') ? 'active' : '' }}" href="{{ route('company.show', $company) }}">Dados da empresa</a>
            @endif
            @if($authUser?->hasPermission('branding.manage'))
                <a class="nav-link {{ request()->routeIs('company.branding.*') ? 'active' : '' }}" href="{{ route('company.branding.edit') }}">Identidade visual</a>
            @endif
            @if($authUser?->hasPermission('onboarding.manage') || $authUser?->hasPermission('onboarding.view'))
                <a class="nav-link {{ request()->routeIs('setup.*') ? 'active' : '' }}" href="{{ route('setup.show') }}">Configuração inicial</a>
            @endif
            @if($authUser?->hasPermission('billing.view'))
                <a class="nav-link {{ request()->routeIs('company.plan.*') ? 'active' : '' }}" href="{{ route('company.plan.show') }}">Plano e uso</a>
                <a class="nav-link {{ request()->routeIs('company.subscription.*') ? 'active' : '' }}" href="{{ route('company.subscription.show') }}">Minha assinatura</a>
            @endif
        </div>
    </div>

    <div class="nav-section {{ $open('company.audit.*', 'company.privacy.*', 'communication.*', 'ai.*', 'training.*') ? 'is-open' : '' }}" data-nav-section>
        <button type="button" class="nav-section-toggle" aria-expanded="{{ $open('company.audit.*', 'company.privacy.*', 'communication.*', 'ai.*', 'training.*') ? 'true' : 'false' }}">
            <span class="nav-section-left">
                <span class="nav-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                Sistema
            </span>
            <span class="nav-chevron" aria-hidden="true"></span>
        </button>
        <div class="nav-section-body">
            @if($authUser?->hasPermission('audit.view'))
                <a class="nav-link {{ request()->routeIs('company.audit.*') ? 'active' : '' }}" href="{{ route('company.audit.index') }}">Auditoria</a>
            @endif
            @if($authUser?->hasPermission('privacy.view'))
                <a class="nav-link {{ request()->routeIs('company.privacy.*') ? 'active' : '' }}" href="{{ route('company.privacy.index') }}">Privacidade</a>
            @endif
            @if($authUser?->hasPermission('communication.view'))
                <a class="nav-link {{ request()->routeIs('communication.*') ? 'active' : '' }}" href="{{ route('communication.messages.index') }}">WhatsApp</a>
            @endif
            @if($authUser?->hasPermission('ai.access'))
                <a class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}" href="{{ route('ai.conversations.index') }}">Assistente Expandor</a>
            @endif
            @if($authUser?->hasPermission('training.manage') || $authUser?->hasPermission('training.view'))
                <a class="nav-link {{ request()->routeIs('training.categories.*', 'training.contents.*') ? 'active' : '' }}" href="{{ route('training.categories.index') }}">Academia</a>
            @endif
        </div>
    </div>
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
