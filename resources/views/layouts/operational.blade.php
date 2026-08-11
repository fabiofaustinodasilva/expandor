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
        $authUser = auth()->user();
        $authUser?->loadMissing('role');
        $company = $authUser?->company;
        $isMap = request()->routeIs('map.*');
        $isFieldSeller = $authUser?->role?->slug === \App\Domains\Company\Models\Role::SELLER;
    @endphp
    <title>@yield('title', $brand->name()) — {{ $brand->systemName }}</title>
    @if($brand->favicon())
        <link rel="icon" href="{{ $brand->favicon() }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@0.469.0"></script>
    <style>
        :root { {{ $themeService->cssVariables($brand) }} }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: {{ $themeService->fontFamily($brand) }};
            background: var(--bg);
            color: var(--text-on-bg);
            overflow: {{ $isMap ? 'hidden' : 'auto' }};
        }
        .op-shell { display: grid; grid-template-columns: 72px minmax(0, 1fr); min-height: 100vh; }
        .op-rail {
            position: sticky; top: 0; height: 100vh;
            background: color-mix(in srgb, var(--secondary) 92%, #000);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column; align-items: center;
            padding: 0.75rem 0.4rem; gap: 0.25rem; z-index: 40;
        }
        .op-rail-brand {
            width: 52px; min-height: 52px; border-radius: 14px; margin-bottom: .35rem;
            display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
            text-decoration: none; color: var(--text); overflow: hidden;
            background: color-mix(in srgb, var(--primary) 18%, transparent);
            border: 1px solid color-mix(in srgb, var(--primary) 35%, transparent);
        }
        .op-rail-brand img { width: 34px; height: 34px; object-fit: contain; }
        .op-rail-brand span { font-size: .55rem; font-weight: 700; max-width: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .op-rail-user {
            width: 100%; padding: .35rem .2rem .55rem; text-align: center;
            border-bottom: 1px solid var(--border); margin-bottom: .35rem;
        }
        .op-rail-user strong { display: block; font-size: .62rem; color: var(--text); line-height: 1.2; max-width: 64px; margin: 0 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .op-rail-user span { display: block; font-size: .55rem; color: var(--muted); margin-top: .15rem; }
        .op-rail a, .op-rail button {
            width: 52px; min-height: 52px; border-radius: 14px;
            display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.15rem; color: var(--muted); border: 0; background: transparent; cursor: pointer;
            text-decoration: none; font-size: 0.62rem; font-weight: 600; transition: .15s ease;
        }
        .op-rail a:hover, .op-rail a.active {
            background: color-mix(in srgb, var(--primary) 22%, transparent); color: var(--text);
        }
        .op-main { position: relative; min-width: 0; min-height: 0; color: var(--text-on-bg); }
        .op-page { padding: 1rem 1.25rem 2rem; max-width: 1100px; margin: 0 auto; }
        .card { background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 1rem; padding: 1.1rem; color: var(--text); }
        .op-toast {
            position: fixed; left: 1rem; right: 1rem; bottom: 5.5rem; z-index: 80;
            background: var(--bg-elevated); border: 1px solid var(--border); color: var(--text);
            padding: 0.9rem 1.1rem; border-radius: 1rem; display: none;
            box-shadow: 0 10px 40px rgba(0,0,0,.35); text-align: center; font-weight: 600;
            max-width: 420px; margin: 0 auto;
        }
        @media (min-width: 901px) {
            .op-toast { left: auto; right: 1.25rem; bottom: 1.25rem; text-align: left; }
        }
        .op-toast.show { display: block; animation: fadeUp .25s ease; }
        .op-toast.success { border-color: color-mix(in srgb, var(--success) 45%, transparent); background: color-mix(in srgb, var(--success) 12%, var(--bg)); }
        .op-toast.error { border-color: color-mix(in srgb, var(--highlight) 45%, transparent); background: color-mix(in srgb, var(--highlight) 12%, var(--bg)); }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .empty-friendly { text-align: center; padding: 2rem 1rem; }
        .empty-friendly p { color: var(--muted); margin: .5rem 0 1.25rem; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border: 1px solid transparent; border-radius: .75rem; padding: .7rem 1rem; font-weight: 700; cursor: pointer; text-decoration: none; color: inherit; transition: .18s ease; min-height: 2.75rem; }
        .btn-primary { background: var(--primary, var(--accent)); color: var(--button-text); }
        .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-danger { background: color-mix(in srgb, var(--highlight) 16%, transparent); color: var(--highlight); border-color: color-mix(in srgb, var(--highlight) 40%, transparent); }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: .7rem .5rem; border-bottom: 1px solid var(--border); text-align: left; font-size: .92rem; }
        .page-title { margin: 0 0 .75rem; font-size: 1.35rem; }
        .header-meta { color: var(--muted); font-size: .9rem; }
        .form-group { margin-bottom: .9rem; }
        .form-group label { display: block; margin-bottom: .35rem; color: var(--muted); font-size: .88rem; }
        .form-control, select, input, textarea {
            width: 100%; background: var(--bg); border: 1px solid var(--border); color: var(--text);
            border-radius: .75rem; padding: .75rem .85rem; box-sizing: border-box;
        }
        .badge { display: inline-block; padding: .2rem .55rem; border-radius: 999px; background: var(--bg-soft); font-size: .75rem; }
        .badge-primary { background: color-mix(in srgb, var(--primary) 22%, transparent); color: var(--text); }
        .badge-success { background: color-mix(in srgb, var(--success) 22%, transparent); }
        .badge-warning { background: color-mix(in srgb, var(--warning) 22%, transparent); }
        .badge-danger { background: color-mix(in srgb, var(--highlight) 22%, transparent); }
        .badge-info { background: color-mix(in srgb, var(--primary) 22%, transparent); }
        .alert { padding: .85rem 1rem; border-radius: .75rem; margin-bottom: 1rem; }
        .alert-success { background: color-mix(in srgb, var(--success) 15%, transparent); border: 1px solid color-mix(in srgb, var(--success) 35%, transparent); }
        .alert-error { background: color-mix(in srgb, var(--highlight) 15%, transparent); border: 1px solid color-mix(in srgb, var(--highlight) 35%, transparent); }
        .actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
        .grid { display: grid; gap: 1rem; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .ux-modal { position: fixed; inset: 0; z-index: 100; display: grid; place-items: center; padding: 1rem; }
        .ux-modal[hidden] { display: none !important; }
        .ux-modal__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.55); }
        .ux-modal__panel { position: relative; z-index: 1; width: min(440px, 100%); }
        @media (min-width: 901px) and (max-width: 1100px) {
            .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/client-ui.css') }}">
    @stack('styles')
</head>
<body class="client-ui {{ $isFieldSeller ? 'field-seller' : '' }}{{ $isMap ? ' map-fullscreen' : '' }}">
<a class="client-skip-link" href="#client-main">Ir para o conteúdo</a>
@php
    $impersonating = session()->has(\App\Domains\Platform\Actions\StartImpersonationAction::SESSION_ADMIN_ID);
@endphp
@if($impersonating)
    <div class="fixed top-0 inset-x-0 z-[90] bg-orange-900 text-white px-4 py-2 flex justify-between gap-3 items-center text-sm">
        <div>Impersonação — {{ $authUser?->name }}</div>
        <form method="POST" action="{{ route('impersonation.exit') }}">@csrf<button class="underline" type="submit">Encerrar</button></form>
    </div>
@endif

<div class="op-shell {{ $impersonating ? 'pt-10' : '' }}" data-nav-open="0">
    <header class="op-mobile-bar" aria-label="Barra mobile">
        <button type="button" class="op-nav-toggle" aria-expanded="false" aria-controls="op-nav-drawer">
            <i data-lucide="menu" class="w-5 h-5" aria-hidden="true"></i>
            <span>Menu</span>
        </button>
        <a href="{{ route('map.index') }}" class="op-mobile-bar__brand" title="{{ $brand->name() }}">
            @if($brand->logoMark())
                <img src="{{ $brand->logoMark() }}" alt="">
            @endif
            <span>{{ $brand->name() }}</span>
        </a>
        <a href="{{ route('operations.more') }}" class="op-mobile-bar__more" title="Mais opções" aria-label="Mais opções">
            <i data-lucide="ellipsis" class="w-5 h-5" aria-hidden="true"></i>
        </a>
    </header>

    <button type="button" class="op-nav-backdrop" hidden aria-hidden="true" aria-label="Fechar menu"></button>

    @include('layouts.partials.client-rail', ['brand' => $brand, 'company' => $company, 'authUser' => $authUser])

    <div class="op-main" id="client-main" tabindex="-1">
        @include('onboarding.partials.trial-banner')
        @include('onboarding.partials.saas-onboarding-banner')
        @if(session('success'))
            <div class="op-page" style="padding-bottom:0;"><div class="alert alert-success">{{ session('success') }}</div></div>
        @endif
        @hasSection('page')
            <div class="op-page">@yield('page')</div>
        @else
            @yield('content')
        @endif
    </div>
</div>

<div id="op-toast" class="op-toast" role="status"></div>
<script>if (window.lucide) { window.lucide.createIcons(); }</script>
@include('partials.rc-ux-polish')
<script src="{{ asset('js/client-mobile.js') }}" defer></script>
@stack('scripts')
@if($brand->customCss)<style>{!! $brand->customCss !!}</style>@endif
</body>
</html>
