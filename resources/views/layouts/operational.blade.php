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
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border: 0; border-radius: .75rem; padding: .7rem 1rem; font-weight: 700; cursor: pointer; text-decoration: none; color: inherit; }
        .btn-primary { background: var(--primary); color: var(--button-text); }
        .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text); }
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
        @media (max-width: 900px) {
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .op-shell { grid-template-columns: 1fr; grid-template-rows: 1fr auto; min-height: 100dvh; }
            .op-rail {
                order: 2; position: sticky; bottom: 0; top: auto; height: 74px; width: 100%;
                flex-direction: row; justify-content: space-around; border-right: 0;
                border-top: 1px solid var(--border); padding: .35rem .2rem;
            }
            .op-rail a, .op-rail button { width: auto; min-width: 64px; min-height: 60px; font-size: .65rem; }
            .op-rail-brand, .op-rail-user { display: none; }
            .op-main { order: 1; min-height: calc(100dvh - 74px); }
            body.field-seller .op-rail a span { font-size: .68rem; }
        }
        .op-rail a.op-rail-field { display: none; }
        body.field-seller .op-rail a.op-rail-admin { display: none !important; }
        body.field-seller .op-rail a.op-rail-field { display: inline-flex !important; }
        body.field-seller .op-rail .flex-1 { display: none; }
        body.field-seller .op-rail { gap: .15rem; }
    </style>
    @stack('styles')
</head>
<body class="{{ $isFieldSeller ? 'field-seller' : '' }}">
@php
    $impersonating = session()->has(\App\Domains\Platform\Actions\StartImpersonationAction::SESSION_ADMIN_ID);
@endphp
@if($impersonating)
    <div class="fixed top-0 inset-x-0 z-[90] bg-orange-900 text-white px-4 py-2 flex justify-between gap-3 items-center text-sm">
        <div>Impersonação — {{ $authUser?->name }}</div>
        <form method="POST" action="{{ route('impersonation.exit') }}">@csrf<button class="underline" type="submit">Encerrar</button></form>
    </div>
@endif

<div class="op-shell {{ $impersonating ? 'pt-10' : '' }}">
    <aside class="op-rail" aria-label="Navegação {{ $brand->name() }}">
        <a href="{{ route('map.index') }}" class="op-rail-brand" title="{{ $brand->name() }}">
            @if($brand->logoMark())
                <img src="{{ $brand->logoMark() }}" alt="{{ $brand->name() }}">
            @else
                <span>{{ \Illuminate\Support\Str::limit($brand->name(), 8, '') }}</span>
            @endif
        </a>
        <div class="op-rail-user op-rail-admin" title="{{ $authUser?->name }}">
            <strong>{{ $authUser?->name }}</strong>
            <span>{{ $authUser?->role?->name ?? ($company?->name ?? '') }}</span>
        </div>
        {{-- Seller: só o essencial de campo --}}
        <a href="{{ route('map.index') }}" class="op-rail-field {{ request()->routeIs('map.*') ? 'active' : '' }}" title="Mapa">
            <i data-lucide="map-pinned" class="w-5 h-5"></i><span>Mapa</span>
        </a>
        @if($authUser?->hasPermission('visits.view'))
            <a href="{{ route('follow-ups.index') }}" class="op-rail-field {{ request()->routeIs('follow-ups.*') ? 'active' : '' }}" title="Agenda">
                <i data-lucide="calendar-clock" class="w-5 h-5"></i><span>Agenda</span>
            </a>
            <a href="{{ route('operations.my-visits') }}" class="op-rail-field {{ request()->routeIs('operations.my-visits') ? 'active' : '' }}" title="Visitas">
                <i data-lucide="clipboard-list" class="w-5 h-5"></i><span>Visitas</span>
            </a>
        @endif
        @if($authUser?->hasPermission('customers.view'))
            <a href="{{ route('customers.index') }}" class="op-rail-field {{ request()->routeIs('customers.*') ? 'active' : '' }}" title="Clientes">
                <i data-lucide="contact" class="w-5 h-5"></i><span>Clientes</span>
            </a>
        @endif
        @if($authUser?->hasPermission('dashboard.view'))
            <a href="{{ route('dashboard') }}" class="op-rail-field {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Resultado">
                <i data-lucide="gauge" class="w-5 h-5"></i><span>Resultado</span>
            </a>
        @endif
        @if($authUser?->hasPermission('commissions.view_self'))
            <a href="{{ route('commissions.index') }}" class="op-rail-field {{ request()->routeIs('commissions.index') ? 'active' : '' }}" title="Minha comissão">
                <i data-lucide="wallet" class="w-5 h-5"></i><span>Comissão</span>
            </a>
        @endif

        {{-- Gestão (admin / gerente) --}}
        <a href="{{ route('map.index') }}" class="op-rail-admin {{ request()->routeIs('map.*') ? 'active' : '' }}" title="Mapa">
            <i data-lucide="map-pinned" class="w-5 h-5"></i><span>Mapa</span>
        </a>
        <a href="{{ route('operations.team') }}" class="op-rail-admin {{ request()->routeIs('operations.team') ? 'active' : '' }}" title="Equipe">
            <i data-lucide="users" class="w-5 h-5"></i><span>Equipe</span>
        </a>
        @if($authUser?->hasPermission('campaigns.view'))
            <a href="{{ route('campaigns.index') }}" class="op-rail-admin {{ request()->routeIs('campaigns.*') ? 'active' : '' }}" title="Campanhas">
                <i data-lucide="target" class="w-5 h-5"></i><span>Campanhas</span>
            </a>
        @endif
        @if($authUser?->hasPermission('visits.view'))
            <a href="{{ route('follow-ups.index') }}" class="op-rail-admin {{ request()->routeIs('follow-ups.*') ? 'active' : '' }}" title="Agenda">
                <i data-lucide="calendar-clock" class="w-5 h-5"></i><span>Agenda</span>
            </a>
        @endif
        @if($authUser?->hasPermission('customers.view'))
            <a href="{{ route('customers.index') }}" class="op-rail-admin {{ request()->routeIs('customers.*') ? 'active' : '' }}" title="Clientes">
                <i data-lucide="contact" class="w-5 h-5"></i><span>Clientes</span>
            </a>
        @endif
        @if($authUser?->hasPermission('dashboard.view'))
            <a href="{{ route('dashboard') }}" class="op-rail-admin {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Resultados">
                <i data-lucide="bar-chart-3" class="w-5 h-5"></i><span>Resultados</span>
            </a>
        @endif
        @if($authUser?->hasPermission('commissions.manage'))
            <a href="{{ route('commissions.index') }}"
               class="op-rail-admin {{ request()->routeIs('commissions.index') ? 'active' : '' }}"
               title="Comissões">
                <i data-lucide="wallet" class="w-5 h-5"></i><span>Comissões</span>
            </a>
            <a href="{{ route('commissions.products.index') }}"
               class="op-rail-admin {{ request()->routeIs('commissions.products.*') ? 'active' : '' }}"
               title="Produtos e estoque">
                <i data-lucide="package" class="w-5 h-5"></i><span>Produtos</span>
            </a>
        @endif
        @if($authUser?->hasPermission('company.manage') || $authUser?->hasPermission('integrations.view') || $authUser?->hasPermission('billing.view') || $authUser?->hasPermission('branding.manage') || $authUser?->hasPermission('commissions.manage'))
            <a href="{{ route('operations.settings') }}"
               class="op-rail-admin {{ request()->routeIs('operations.settings*', 'operations.integrations') ? 'active' : '' }}"
               title="Configurações">
                <i data-lucide="settings" class="w-5 h-5"></i><span>Config</span>
            </a>
        @endif
        <div class="flex-1 op-rail-admin"></div>
        <a href="{{ route('profile.edit') }}" class="op-rail-admin {{ request()->routeIs('profile.*') ? 'active' : '' }}" title="Meu perfil">
            <i data-lucide="user-round" class="w-5 h-5"></i><span>Perfil</span>
        </a>
        <a href="{{ route('operations.more') }}" class="op-rail-admin {{ request()->routeIs('operations.more') ? 'active' : '' }}" title="Mais opções">
            <i data-lucide="ellipsis" class="w-5 h-5"></i><span>Mais</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" title="Sair"><i data-lucide="log-out" class="w-5 h-5"></i><span>Sair</span></button>
        </form>
    </aside>

    <div class="op-main">
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
@stack('scripts')
@if($brand->customCss)<style>{!! $brand->customCss !!}</style>@endif
</body>
</html>
