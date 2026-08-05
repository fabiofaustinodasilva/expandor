<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administração') — {{ config('app.name') }}</title>
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
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.55rem 0.75rem;
            border-radius: 0.55rem;
            color: var(--muted);
            transition: background 0.16s ease, color 0.16s ease, box-shadow 0.16s ease;
            font-size: 0.9rem;
            font-weight: 520;
            position: relative;
        }
        .nav-link:hover {
            background: var(--bg-soft);
            color: var(--text);
        }
        .nav-link.active {
            background: color-mix(in srgb, var(--accent) 16%, var(--bg-soft));
            color: var(--text);
            box-shadow: inset 3px 0 0 var(--accent);
        }
        .ent-nav { display: flex; flex-direction: column; gap: 0.35rem; }
        .nav-section {
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .nav-section-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.65rem 0.7rem;
            border: 0;
            background: transparent;
            color: var(--text);
            cursor: pointer;
            font-weight: 650;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-radius: 0.65rem;
        }
        .nav-section-toggle:hover { background: rgba(255,255,255,0.03); }
        .nav-section-left { display: inline-flex; align-items: center; gap: 0.55rem; }
        .nav-ico {
            width: 1.15rem; height: 1.15rem; display: inline-grid; place-items: center; color: var(--muted);
        }
        .nav-ico svg { width: 1.05rem; height: 1.05rem; }
        .nav-chevron {
            width: 0.45rem; height: 0.45rem;
            border-right: 1.5px solid var(--muted);
            border-bottom: 1.5px solid var(--muted);
            transform: rotate(-45deg);
            transition: transform 0.18s ease;
            opacity: 0.7;
        }
        .nav-section.is-open .nav-chevron { transform: rotate(45deg); }
        .nav-section-body {
            display: none;
            flex-direction: column;
            gap: 0.15rem;
            padding: 0 0.25rem 0.45rem 0.35rem;
        }
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
            {{ strtoupper($platformBrand->name()) }} <span>Admin</span>
        </div>

        @include('layouts.partials.platform-nav')
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
