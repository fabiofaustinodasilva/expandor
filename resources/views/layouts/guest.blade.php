<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Expandor') — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #0F1117;
            --bg-elevated: #171A22;
            --bg-soft: #1E2330;
            --border: #2A3142;
            --text: #F3F5F9;
            --muted: #9AA3B5;
            --accent: #3B82F6;
            --accent-2: #EF4444;
            --success: #22C55E;
            --button-text: #fff;
            --primary: #3B82F6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top, #1d2433, var(--bg) 55%);
            color: var(--text);
            min-height: 100vh;
        }
        a { color: var(--accent); }
        .wrap { max-width: 960px; margin: 0 auto; padding: 2rem 1rem; }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .muted { color: var(--muted); }
        .error { color: #fca5a5; margin-bottom: 1rem; }
        .topnav { display:flex; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; align-items:center; }
        .topnav a { text-decoration: none; color: var(--muted); font-weight: 600; }
        .topnav a:hover { color: var(--text); }
        .guest-btn-row { display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap; }
        .guest-btn-row .btn { width: auto; }
        .wrap form .btn,
        .wrap form button[type="submit"] { width: 100%; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topnav">
        <strong><a href="{{ route('marketplace.home') }}" style="color:inherit; text-decoration:none;">{{ config('app.name') }}</a></strong>
        <div class="guest-btn-row">
            <a href="{{ route('marketplace.plans') }}">Planos</a>
            <a class="btn btn-ghost" href="{{ route('login') }}" style="width:auto;">Já tenho conta</a>
        </div>
    </div>
    @if($errors->any())
        <div class="alert alert-error" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @yield('content')
</div>
@include('partials.rc-ux-polish')
<style>
    @media (max-width: 900px) {
        .topnav { flex-direction: column; align-items: flex-start; }
        .wrap { padding: 1.25rem 1rem; }
        .guest-btn-row { width: 100%; }
    }
</style>
</body>
</html>
