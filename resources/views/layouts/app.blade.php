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
    <title>@yield('title', 'Painel') — {{ $brand->systemName }}</title>
    @if($brand->faviconUrl)
        <link rel="icon" href="{{ $brand->faviconUrl }}">
    @endif
    @include('layouts.partials.seller-vendor', ['includeCss' => false])
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
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.55rem 0.75rem;
            border-radius: 0.55rem;
            color: var(--muted);
            transition: background 0.16s ease, color 0.16s ease, box-shadow 0.16s ease;
            font-size: 0.9rem;
            font-weight: 520;
        }
        .nav-link:hover { background: var(--bg-soft); color: var(--text); }
        .nav-link.active {
            background: color-mix(in srgb, var(--accent) 16%, var(--bg-soft));
            color: var(--text);
            box-shadow: inset 3px 0 0 var(--accent);
        }
        .nav-link.disabled { opacity: 0.45; pointer-events: none; }
        .ent-nav { display: flex; flex-direction: column; gap: 0.35rem; }
        .nav-section { border-radius: 0.75rem; overflow: hidden; }
        .nav-section-toggle {
            width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
            padding: 0.65rem 0.7rem; border: 0; background: transparent; color: var(--text);
            cursor: pointer; font-weight: 650; font-size: 0.78rem; text-transform: uppercase;
            letter-spacing: 0.06em; border-radius: 0.65rem;
        }
        .nav-section-toggle:hover { background: rgba(255,255,255,0.03); }
        .nav-section-left { display: inline-flex; align-items: center; gap: 0.55rem; }
        .nav-ico { width: 1.15rem; height: 1.15rem; display: inline-grid; place-items: center; color: var(--muted); }
        .nav-ico svg { width: 1.05rem; height: 1.05rem; }
        .nav-chevron {
            width: 0.45rem; height: 0.45rem; border-right: 1.5px solid var(--muted); border-bottom: 1.5px solid var(--muted);
            transform: rotate(-45deg); transition: transform 0.18s ease; opacity: 0.7;
        }
        .nav-section.is-open .nav-chevron { transform: rotate(45deg); }
        .nav-section-body { display: none; flex-direction: column; gap: 0.15rem; padding: 0 0.25rem 0.45rem 0.35rem; }
        .nav-section.is-open .nav-section-body { display: flex; }
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
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stat-label { color: var(--muted); font-size: 0.85rem; margin-bottom: 0.35rem; }
        .stat-value { font-size: 1.4rem; font-weight: 700; }
        .shortcut {
            display: block;
            padding: 1rem;
            border-radius: var(--ds-radius, 0.85rem);
            border: 1px dashed var(--border);
            color: var(--muted);
            background: rgba(30, 35, 48, 0.4);
            transition: border-color 0.18s ease, background 0.18s ease;
        }
        .shortcut:hover { border-color: var(--accent); background: rgba(30, 35, 48, 0.55); }
        .nav-link { transition: background 0.18s ease, color 0.18s ease; }
        @media (max-width: 900px) {
            .shell { flex-direction: column; }
            .sidebar { width: 100%; border-right: 0; }
            .header { flex-wrap: wrap; }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/client-ui.css') }}">
</head>
<body class="client-ui">
<a class="client-skip-link" href="#client-main">Ir para o conteúdo</a>
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
<div class="shell" id="app-shell">
    <aside class="sidebar" id="app-sidebar">
        <div class="brand" style="display:flex; align-items:center; gap:0.65rem;">
            @if($brand->logoUrl)
                <img src="{{ $brand->logoUrl }}" alt="{{ $brand->displayName }}" style="height:28px; max-width:120px; object-fit:contain;">
            @endif
            <span>{{ $brand->displayName }}</span>
        </div>
        @include('layouts.partials.app-nav')
    </aside>

    <div class="main">
        @include('onboarding.partials.trial-banner')
        @include('onboarding.partials.saas-onboarding-banner')
        <header class="header">
            <div>
                <button type="button" class="shell-nav-toggle" aria-expanded="false" aria-controls="app-sidebar">Menu</button>
                <div class="header-meta" style="margin-top:0.35rem;">Empresa atual</div>
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

        <main class="content" id="client-main" tabindex="-1">
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

            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if(!empty($billingBannerInvoice) && !request()->routeIs('company.finance.*'))
                @include('payments.finance._banner', [
                    'currentInvoice' => $billingBannerInvoice,
                    'company' => $authUser?->company,
                    'withinGrace' => $billingBannerWithinGrace ?? false,
                    'daysPastDue' => $billingBannerDaysPastDue ?? 0,
                ])
            @endif

            @yield('content')
        </main>
    </div>
</div>
@if($brand->customCss)
    <style>{!! $brand->customCss !!}</style>
@endif
<script>if (window.lucide) { window.lucide.createIcons(); }</script>
@include('partials.rc-ux-polish')
<script src="{{ asset('js/client-mobile.js') }}" defer></script>
</body>
</html>
