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
        .card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.25rem;
        }
        .page-title { font-size: 1.6rem; font-weight: 700; }
        .grid { display: grid; gap: 1rem; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td {
            text-align: left;
            padding: 0.75rem 0.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        .table th { color: var(--muted); font-size: 0.8rem; font-weight: 600; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: var(--bg-soft);
            font-size: 0.75rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border: 0;
            border-radius: 0.65rem;
            padding: 0.65rem 1rem;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-primary { background: var(--accent); color: #111; }
        .btn-ghost { background: transparent; color: var(--text); border: 1px solid var(--border); }
        .form-control, .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            background: var(--bg-soft);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 0.65rem;
            padding: 0.7rem 0.85rem;
        }
        .form-group label { display: block; margin-bottom: 0.35rem; color: var(--muted); font-size: 0.9rem; }
        .actions { display: flex; gap: 0.75rem; align-items: center; }
        .alert { padding: 0.85rem 1rem; border-radius: 0.75rem; margin-bottom: 1rem; }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.35); }
        .alert-error { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); }
        @media (max-width: 900px) {
            .shell { flex-direction: column; }
            .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid var(--border); }
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@php
    $authUser = auth()->user();
@endphp
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            @php $platformBrand = app(\App\Domains\Platform\Services\PlatformBrandingService::class)->payload(); @endphp
            @if($platformBrand->logoMark())
                <img src="{{ $platformBrand->logoMark() }}" alt="{{ $platformBrand->name() }}" style="height:28px; max-width:120px; object-fit:contain; vertical-align:middle; margin-right:.4rem;">
            @endif
            {{ strtoupper($platformBrand->name()) }} <span>Platform</span>
        </div>

        <nav>
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
                    <div class="nav-label" style="margin-top:0.85rem;">Marketplace</div>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.settings.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.settings.edit') }}">Configuração</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.sections.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.sections.index') }}">Seções</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.media.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.media.index') }}">Mídias</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.leads.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.leads.index') }}">Leads</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.analytics') ? 'active' : '' }}" href="{{ route('platform.marketplace.analytics') }}">Analytics</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.intelligence') ? 'active' : '' }}" href="{{ route('platform.marketplace.intelligence') }}">Intelligence</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.pipeline.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.pipeline.index') }}">Pipeline</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.segments.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.segments.index') }}">Segmentos</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.cases.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.cases.index') }}">Cases</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.campaigns.*') ? 'active' : '' }}" href="{{ route('platform.marketplace.campaigns.index') }}">Campanhas</a>
                    <a class="nav-link {{ request()->routeIs('platform.marketplace.preview') ? 'active' : '' }}" href="{{ route('platform.marketplace.preview') }}" target="_blank" rel="noopener">Visualizar Marketplace</a>
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
                <div class="header-meta">Administração SaaS</div>
                <strong>Proprietário da plataforma</strong>
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
