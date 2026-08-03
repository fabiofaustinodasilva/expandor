<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        /** @var \App\Domains\Branding\DTOs\BrandPayload $brand */
        $brand = $brand ?? \App\Domains\Branding\DTOs\BrandPayload::defaults();
        $themeService = app(\App\Domains\Branding\Services\ThemeService::class);
    @endphp
    <title>@yield('title', 'Dashboard') — {{ $brand->systemName }}</title>
    @if($brand->faviconUrl)
        <link rel="icon" href="{{ $brand->faviconUrl }}">
    @endif
    <style>
        :root {
            {{ $themeService->cssVariables($brand) }}
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: {{ $themeService->fontFamily($brand) }};
            background: {{ $themeService->bodyBackground($brand) }};
            color: var(--text-on-bg);
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: rgba(23, 26, 34, 0.95);
            border-right: 1px solid var(--border);
            padding: 1.25rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .brand { font-size: 1.15rem; font-weight: 700; letter-spacing: 0.02em; }
        .brand span { color: var(--accent); }
        .nav-group { display: flex; flex-direction: column; gap: 0.35rem; }
        .nav-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin: 0.75rem 0.6rem 0.35rem;
        }
        .nav-link {
            display: block;
            padding: 0.7rem 0.85rem;
            border-radius: 0.65rem;
            color: var(--muted);
            transition: 0.15s ease;
        }
        .nav-link:hover, .nav-link.active {
            background: var(--bg-soft);
            color: var(--text);
        }
        .nav-link.disabled {
            opacity: 0.45;
            pointer-events: none;
        }
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: rgba(15, 17, 23, 0.75);
            backdrop-filter: blur(8px);
        }
        .header-meta { color: var(--muted); font-size: 0.9rem; }
        .header-user { text-align: right; }
        .header-user strong { display: block; }
        .content { padding: 1.5rem; }
        .card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.25rem;
            color: var(--text);
        }
        .grid { display: grid; gap: 1rem; }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stat-label { color: var(--muted); font-size: 0.85rem; margin-bottom: 0.35rem; }
        .stat-value { font-size: 1.4rem; font-weight: 700; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border: 0;
            border-radius: 0.65rem;
            padding: 0.65rem 1rem;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-primary { background: var(--accent); color: var(--button-text); }
        .btn-danger { background: var(--accent-2); color: var(--button-text); }
        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
        }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td {
            text-align: left;
            padding: 0.85rem 0.6rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
        }
        .table th { color: var(--muted); font-weight: 600; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            background: var(--bg-soft);
            color: var(--text);
        }
        .badge-success { background: rgba(34, 197, 94, 0.15); color: var(--success); }
        .badge-warning { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
        .badge-danger { background: rgba(239, 68, 68, 0.15); color: var(--accent-2); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.35rem; color: var(--muted); font-size: 0.9rem; }
        .form-control {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 0.65rem;
            padding: 0.7rem 0.85rem;
        }
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
        }
        .alert-success { background: rgba(34, 197, 94, 0.12); color: var(--success); }
        .alert-error { background: rgba(239, 68, 68, 0.12); color: #fca5a5; }
        .page-title { margin: 0 0 1rem; font-size: 1.5rem; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .shortcut {
            display: block;
            padding: 1rem;
            border-radius: 0.85rem;
            border: 1px dashed var(--border);
            color: var(--muted);
            background: rgba(30, 35, 48, 0.4);
        }
        @media (max-width: 960px) {
            .shell { flex-direction: column; }
            .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid var(--border); }
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
@php
    $authUser = auth()->user();
    $company = $authUser?->company;
    $impersonating = session()->has(\App\Domains\Platform\Actions\StartImpersonationAction::SESSION_ID);
@endphp
@if($impersonating)
    <div style="background:#7c2d12; color:#fff; padding:0.65rem 1rem; display:flex; justify-content:space-between; gap:1rem; align-items:center;">
        <div>Modo impersonação ativo — você está visualizando como <strong>{{ $authUser?->name }}</strong> ({{ $authUser?->email }}).</div>
        <form method="POST" action="{{ route('impersonation.exit') }}">
            @csrf
            <button class="btn btn-ghost" type="submit" style="border-color:rgba(255,255,255,0.35); color:#fff;">Encerrar impersonação</button>
        </form>
    </div>
@endif
<div class="shell">
    <aside class="sidebar">
        <div class="brand" style="display:flex; align-items:center; gap:0.65rem;">
            @if($brand->logoUrl)
                <img src="{{ $brand->logoUrl }}" alt="{{ $brand->displayName }}" style="height:28px; max-width:120px; object-fit:contain;">
            @endif
            <span>{{ $brand->displayName }}</span>
        </div>

        <nav>
            <div class="nav-group">
                <div class="nav-label">Principal</div>
                @if($authUser?->hasPermission('sales_app.access'))
                    <a class="nav-link {{ request()->routeIs('sales-app.*') ? 'active' : '' }}" href="{{ route('sales-app.dashboard') }}">App Campo</a>
                @endif
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                @if($authUser?->hasPermission('maps.view'))
                    <a class="nav-link {{ request()->routeIs('map.*') ? 'active' : '' }}" href="{{ route('map.index') }}">Mapa operacional</a>
                @endif
            </div>

            <div class="nav-group">
                <div class="nav-label">Território</div>
                @if($authUser?->hasPermission('cities.view'))
                    <a class="nav-link {{ request()->routeIs('cities.*') ? 'active' : '' }}" href="{{ route('cities.index') }}">Cidades</a>
                @endif
                @if($authUser?->hasPermission('sectors.view'))
                    <a class="nav-link {{ request()->routeIs('sectors.*') ? 'active' : '' }}" href="{{ route('sectors.index') }}">Setores</a>
                @endif
                @if($authUser?->hasPermission('properties.view'))
                    <a class="nav-link {{ request()->routeIs('addresses.*') ? 'active' : '' }}" href="{{ route('addresses.index') }}">Endereços</a>
                    <a class="nav-link {{ request()->routeIs('properties.*', 'residents.*') ? 'active' : '' }}" href="{{ route('properties.index') }}">Clientes / Pontos</a>
                @endif
            </div>

            <div class="nav-group">
                <div class="nav-label">Equipe</div>
                @if($authUser?->role?->slug === \App\Domains\Company\Models\Role::ADMINISTRATOR)
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Usuários</a>
                @endif
                @if($authUser?->hasPermission('users.view') || $authUser?->hasPermission('users.manage'))
                    <a class="nav-link {{ request()->routeIs('operations.team') ? 'active' : '' }}" href="{{ route('operations.team') }}">Central da Equipe</a>
                @endif
            </div>

            <div class="nav-group">
                <div class="nav-label">Sistema</div>
                @if($authUser?->hasPermission('company.manage') && $company)
                    <a class="nav-link {{ request()->routeIs('company.show', 'company.edit') ? 'active' : '' }}" href="{{ route('company.show', $company) }}">Empresa</a>
                @endif
                @if($authUser?->hasPermission('branding.manage'))
                    <a class="nav-link {{ request()->routeIs('company.branding.*') ? 'active' : '' }}" href="{{ route('company.branding.edit') }}">Branding</a>
                @endif
                @if($authUser?->hasPermission('onboarding.manage') || $authUser?->hasPermission('onboarding.view'))
                    <a class="nav-link {{ request()->routeIs('setup.*') ? 'active' : '' }}" href="{{ route('setup.show') }}">Setup</a>
                @endif
                @if($authUser?->hasPermission('billing.view'))
                    <a class="nav-link {{ request()->routeIs('company.plan.*') ? 'active' : '' }}" href="{{ route('company.plan.show') }}">Plano e uso</a>
                    <a class="nav-link {{ request()->routeIs('company.subscription.*') ? 'active' : '' }}" href="{{ route('company.subscription.show') }}">Minha Assinatura</a>
                @endif
                @if($authUser?->hasPermission('audit.view'))
                    <a class="nav-link {{ request()->routeIs('company.audit.*') ? 'active' : '' }}" href="{{ route('company.audit.index') }}">Auditoria</a>
                @endif
                @if($authUser?->hasPermission('privacy.view'))
                    <a class="nav-link {{ request()->routeIs('company.privacy.*') ? 'active' : '' }}" href="{{ route('company.privacy.index') }}">Privacidade</a>
                @endif
            </div>

            <div class="nav-group">
                <div class="nav-label">Operação</div>
                @if($authUser?->hasPermission('crm.view'))
                    <a class="nav-link {{ request()->routeIs('crm.*') ? 'active' : '' }}" href="{{ route('crm.dashboard') }}">CRM Comercial</a>
                @endif
                @if($authUser?->hasPermission('commissions.manage'))
                    <a class="nav-link {{ request()->routeIs('commissions.index') ? 'active' : '' }}" href="{{ route('commissions.index') }}">💰 Comissões</a>
                    <a class="nav-link {{ request()->routeIs('commissions.products.*') ? 'active' : '' }}" href="{{ route('commissions.products.index') }}">📦 Produtos / Estoque</a>
                @elseif($authUser?->hasPermission('commissions.view_self'))
                    <a class="nav-link {{ request()->routeIs('commissions.index') ? 'active' : '' }}" href="{{ route('commissions.index') }}">💰 Minha comissão</a>
                @endif
                @if($authUser?->hasPermission('campaigns.view'))
                    <a class="nav-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}" href="{{ route('campaigns.index') }}">Campanhas</a>
                @endif
                @if($authUser?->hasPermission('visits.view'))
                    <a class="nav-link {{ request()->routeIs('follow-ups.*', 'visits.*', 'campaigns.visits.*') ? 'active' : '' }}" href="{{ route('follow-ups.index') }}">Agenda</a>
                @endif
            </div>

            <div class="nav-group">
                <div class="nav-label">Comunicação</div>
                @if($authUser?->hasPermission('communication.view'))
                    <a class="nav-link {{ request()->routeIs('communication.*') ? 'active' : '' }}" href="{{ route('communication.messages.index') }}">WhatsApp</a>
                @endif
            </div>

            <div class="nav-group">
                <div class="nav-label">Inteligência</div>
                @if($authUser?->hasPermission('ai.access'))
                    <a class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}" href="{{ route('ai.conversations.index') }}">Expandor AI</a>
                @endif
            </div>

            <div class="nav-group">
                <div class="nav-label">Futuro</div>
                @if($authUser?->hasPermission('training.manage') || $authUser?->hasPermission('training.view'))
                    <a class="nav-link {{ request()->routeIs('training.categories.*', 'training.contents.*') ? 'active' : '' }}" href="{{ route('training.categories.index') }}">Academia</a>
                @endif
            </div>
        </nav>
    </aside>

    <div class="main">
        @include('onboarding.partials.trial-banner')
        @include('onboarding.partials.saas-onboarding-banner')
        <header class="header">
            <div>
                <div class="header-meta">Empresa atual</div>
                <strong>{{ $company?->name ?? '—' }}</strong>
            </div>
            <div class="header-user">
                <strong>{{ $authUser?->name }}</strong>
                <div class="header-meta">{{ $authUser?->role?->name }} · {{ $authUser?->email }}</div>
                <form method="POST" action="{{ route('logout') }}" style="margin-top: 0.5rem;">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Sair</button>
                </form>
            </div>
        </header>

        <main class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0; padding-left:1.1rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@if($brand->customCss)
    <style>{!! $brand->customCss !!}</style>
@endif
</body>
</html>
