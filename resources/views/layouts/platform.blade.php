<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Platform') — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #0F1117;
            --bg-elevated: #171A22;
            --bg-soft: #1E2330;
            --border: #2A3142;
            --text: #F3F5F9;
            --muted: #9AA3B5;
            --accent: #F59E0B;
            --accent-2: #EF4444;
            --success: #22C55E;
            --warning: #F59E0B;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top right, #2a2110, var(--bg) 45%);
            color: var(--text);
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
        .grid { display: grid; gap: 1rem; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .nav-link { transition: background var(--ds-transition, .18s ease), color var(--ds-transition, .18s ease); }
        @media (max-width: 900px) {
            .shell { flex-direction: column; }
            .sidebar { width: 100%; border-right: 0; }
        }
    </style>
</head>
<body>
@php
    $authUser = auth()->user();
@endphp
<div class="shell" id="platform-shell">
    <aside class="sidebar" id="platform-sidebar">
        <div class="brand">
            @php $platformBrand = app(\App\Domains\Platform\Services\PlatformBrandingService::class)->payload(); @endphp
            @if($platformBrand->logoMark())
                <img src="{{ $platformBrand->logoMark() }}" alt="{{ $platformBrand->name() }}" style="height:28px; max-width:120px; object-fit:contain; vertical-align:middle; margin-right:.4rem;">
            @endif
            {{ strtoupper($platformBrand->name()) }} <span>Platform</span>
        </div>

        <nav aria-label="Menu da plataforma">
            <div class="nav-group">
                <div class="nav-label">Plataforma</div>
                <a class="nav-link {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}" href="{{ route('platform.dashboard') }}">Dashboard</a>
                <a class="nav-link {{ request()->routeIs('platform.activation.*') ? 'active' : '' }}" href="{{ route('platform.activation.index') }}">SaaS Health</a>
                <a class="nav-link {{ request()->routeIs('platform.saas.intelligence*') ? 'active' : '' }}" href="{{ route('platform.saas.intelligence') }}">SaaS Intelligence</a>
                <a class="nav-link {{ request()->routeIs('platform.profile.*') ? 'active' : '' }}" href="{{ route('platform.profile.edit') }}">Meu perfil</a>
                @can('platform.manageCompanies')
                    <a class="nav-link {{ request()->routeIs('platform.companies.*') ? 'active' : '' }}" href="{{ route('platform.companies.index') }}">Empresas</a>
                @endcan
                @can('platform.managePlans')
                    <a class="nav-link {{ request()->routeIs('platform.plans.*') ? 'active' : '' }}" href="{{ route('platform.plans.index') }}">Planos</a>
                @endcan
                @can('platform.manageCompanies')
                    <a class="nav-link {{ request()->routeIs('platform.billing.*') ? 'active' : '' }}" href="{{ route('platform.billing.index') }}">Billing</a>
                @endcan
                @can('platform.manageBranding')
                    <a class="nav-link {{ request()->routeIs('platform.branding.*') ? 'active' : '' }}" href="{{ route('platform.branding.edit') }}">Branding</a>
                @endcan
                @can('marketplace.manage')
                    <div class="nav-label" style="margin-top:0.85rem;">Site público</div>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.settings.*') && !request()->routeIs('platform.marketplace.mercadopago*') ? 'active' : '' }}" href="{{ route('platform.marketplace.settings.edit') }}">Geral & Landing</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.sections.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.sections.index') }}">Seções</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.media.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.media.index') }}">Conteúdo & Mídias</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.mercadopago*') ? 'active' : '' }}" href="{{ route('platform.marketplace.mercadopago.edit') }}">Mercado Pago</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.preview') ? 'active' : '' }}" href="{{ route('platform.marketplace.preview') }}" target="_blank" rel="noopener">Visualizar site</a>
                    <div class="nav-label" style="margin-top:0.85rem;">Crescimento</div>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.leads.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.leads.index') }}">Leads</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.analytics') ? 'active' : '' }}" href="{{ route('platform.marketplace.analytics') }}">Analytics</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.intelligence') ? 'active' : '' }}" href="{{ route('platform.marketplace.intelligence') }}">Intelligence</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.pipeline.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.pipeline.index') }}">Pipeline</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.segments.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.segments.index') }}">Segmentos</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.cases.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.cases.index') }}">Cases</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.campaigns.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.campaigns.index') }}">Campanhas</a>
                @endcan
                @can('platform.manageFeatureFlags')
                    <a class="nav-link {{ request()->routeIs('platform.flags.*') ? 'active' : '' }}" href="{{ route('platform.flags.index') }}">Feature Flags</a>
                @endcan
                @can('platform.viewHealth')
                    <a class="nav-link {{ request()->routeIs('platform.health.*') ? 'active' : '' }}" href="{{ route('platform.health.index') }}">Health Score</a>
                @endcan
            </div>
        </nav>
    </aside>

    <div class="main">
        <header class="header">
            <div>
                <button type="button" class="shell-nav-toggle" aria-expanded="false" aria-controls="platform-sidebar">Menu</button>
                <div class="header-meta" style="margin-top:0.35rem;">Administração</div>
                <strong>Painel da plataforma</strong>
            </div>
            <div class="header-user">
                <strong>{{ $authUser?->name }}</strong>
                <div class="header-meta">{{ $authUser?->email }}</div>
                <div class="actions" style="margin-top:0.5rem; justify-content:flex-end;">
                    <a class="btn btn-ghost" href="{{ route('platform.profile.edit') }}">Perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-ghost" type="submit">Sair</button>
                    </form>
                </div>
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
@include('partials.rc-ux-polish')
@stack('scripts')
</body>
</html>
