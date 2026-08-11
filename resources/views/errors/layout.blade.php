<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $brand = \App\Domains\Branding\DTOs\BrandPayload::defaults();
        $theme = app(\App\Domains\Branding\Services\ThemeService::class);
        $name = $brand->name() !== '' ? $brand->name() : 'Expandor';
    @endphp
    <title>{{ $title }} — {{ $name }}</title>
    <style>
        :root { {{ $theme->cssVariables($brand) }} }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            padding: 1.25rem; font-family: {{ $theme->fontFamily($brand) }};
            background: var(--bg); color: var(--text-on-bg);
        }
        .card {
            width: min(28rem, 100%); background: var(--bg-elevated); border: 1px solid var(--border);
            border-radius: 1rem; padding: 1.5rem 1.35rem;
        }
        h1 { margin: 0 0 .5rem; font-size: 1.35rem; }
        p { margin: 0 0 1.15rem; color: var(--muted); line-height: 1.45; }
        .code { font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: .4rem; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 2.75rem; padding: .55rem 1rem; border-radius: .75rem;
            background: var(--primary); color: var(--button-text, #fff); text-decoration: none; font-weight: 700;
        }
        .btn:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <div class="code">{{ $code }}</div>
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        <a class="btn" href="{{ url('/') }}">Voltar ao início</a>
    </main>
</body>
</html>
