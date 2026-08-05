<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F1117">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    @php
        /** @var \App\Domains\Branding\DTOs\BrandPayload $brand */
        $brand = $brand ?? \App\Domains\Branding\DTOs\BrandPayload::defaults();
        $themeService = app(\App\Domains\Branding\Services\ThemeService::class);
    @endphp
    <title>@yield('title', 'Campo') — {{ $brand->name() }}</title>
    @if($brand->favicon())
        <link rel="icon" href="{{ $brand->favicon() }}">
    @endif
    <style>
        :root {
            {{ $themeService->cssVariables($brand) }}
            --surface: var(--bg-elevated);
            --surface-2: var(--bg-soft);
            --danger: var(--highlight);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: {{ $themeService->fontFamily($brand) }};
            background: {{ $themeService->bodyBackground($brand) }};
            color: var(--text);
            min-height: 100vh;
            padding-bottom: calc(72px + var(--safe-bottom));
        }
        a { color: inherit; text-decoration: none; }
        .app-top {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: rgba(15, 17, 23, 0.92);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }
        .app-brand { font-weight: 700; letter-spacing: 0.02em; }
        .app-brand span { color: var(--accent); }
        .app-meta { color: var(--muted); font-size: 0.8rem; text-align: right; }
        .app-content { padding: 1rem; max-width: 720px; margin: 0 auto; }
        .page-title { margin: 0 0 0.35rem; font-size: 1.35rem; }
        .page-sub { color: var(--muted); margin: 0 0 1rem; font-size: 0.9rem; }
        .card { margin-bottom: 0.85rem; }
        .card-link { display: block; }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.65rem;
            margin-bottom: 1rem;
        }
        .stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.9rem;
            padding: 0.85rem 0.65rem;
            text-align: center;
        }
        .stat-label { color: var(--muted); font-size: 0.72rem; margin-bottom: 0.25rem; }
        .stat-value { font-size: 1.25rem; font-weight: 700; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.72rem;
            background: var(--surface-2);
        }
        .badge-success { background: rgba(34,197,94,.15); color: var(--success); }
        .badge-warning { background: rgba(245,158,11,.15); color: var(--warning); }
        .btn { width: 100%; padding: 0.85rem 1rem; font-size: 1rem; border-radius: 0.85rem; }
        .btn-row { display: grid; gap: 0.55rem; margin-top: 0.85rem; }
        .list-title { font-weight: 700; margin-bottom: 0.2rem; }
        .list-meta { color: var(--muted); font-size: 0.85rem; }
        .bottom-nav {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.25rem;
            padding: 0.45rem 0.55rem calc(0.45rem + var(--safe-bottom));
            background: rgba(23, 26, 34, 0.96);
            border-top: 1px solid var(--border);
            backdrop-filter: blur(10px);
            z-index: 30;
        }
        .bottom-nav a {
            text-align: center;
            padding: 0.55rem 0.35rem;
            border-radius: 0.75rem;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 600;
        }
        .bottom-nav a.active {
            background: var(--surface-2);
            color: var(--text);
        }
        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.55rem;
        }
        .status-option {
            display: block;
            padding: 0.75rem;
            border-radius: 0.85rem;
            border: 1px solid var(--border);
            background: var(--bg);
            text-align: center;
            font-size: 0.85rem;
        }
        .status-option input { margin-bottom: 0.35rem; }
        .empty { color: var(--muted); text-align: center; padding: 1.5rem 0.5rem; }
        @media (min-width: 768px) {
            .app-content { padding-top: 1.25rem; }
            .btn-row { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
@php
    $authUser = auth()->user();
@endphp
<header class="app-top">
    <div class="app-brand">EXPANDOR <span>Campo</span></div>
    <div class="app-meta">
        <div>{{ $authUser?->name }}</div>
        <form method="POST" action="{{ route('logout') }}" style="margin-top:0.25rem;">
            @csrf
            <button type="submit" class="btn btn-ghost" style="width:auto; padding:0.35rem 0.65rem; font-size:0.75rem;">Sair</button>
        </form>
    </div>
</header>

<main class="app-content">
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

<nav class="bottom-nav" aria-label="Navegação do app de campo">
    <a class="{{ request()->routeIs('sales-app.dashboard') ? 'active' : '' }}" href="{{ route('sales-app.dashboard') }}">Início</a>
    <a class="{{ request()->routeIs('sales-app.campaigns.*') ? 'active' : '' }}" href="{{ route('sales-app.campaigns.index') }}">Campanhas</a>
    <a class="{{ request()->routeIs('sales-app.follow-ups.*') ? 'active' : '' }}" href="{{ route('sales-app.follow-ups.index') }}">Retornos</a>
    <a class="{{ request()->routeIs('sales-app.training.*') ? 'active' : '' }}" href="{{ route('sales-app.training.index') }}">Academia</a>
</nav>
@include('partials.rc-ux-polish')
</body>
</html>
