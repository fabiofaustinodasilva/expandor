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
            --border: #2A3142;
            --text: #F3F5F9;
            --muted: #9AA3B5;
            --accent: #3B82F6;
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
        .card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.25rem;
        }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .muted { color: var(--muted); }
        label { display:block; margin-bottom:0.35rem; color:var(--muted); font-size:0.9rem; }
        input, select {
            width: 100%;
            margin-bottom: 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 0.65rem;
            padding: 0.75rem 0.85rem;
        }
        button, .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            border: 0;
            border-radius: 0.65rem;
            padding: 0.8rem 1rem;
            background: var(--accent);
            color: white;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .error { color: #fca5a5; margin-bottom: 1rem; }
        .topnav { display:flex; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topnav">
        <strong><a href="{{ route('marketplace.home') }}" style="color:inherit; text-decoration:none;">{{ config('app.name') }}</a></strong>
        <div style="display:flex; gap:1rem; align-items:center;">
            <a href="{{ route('marketplace.plans') }}">Planos</a>
            <a href="{{ route('login') }}">Já tenho conta</a>
        </div>
    </div>
    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @yield('content')
</div>
@include('partials.rc-ux-polish')
<style>
    @media (max-width: 768px) {
        .topnav { flex-direction: column; align-items: flex-start; }
        .wrap { padding: 1.25rem 1rem; }
    }
</style>
</body>
</html>
