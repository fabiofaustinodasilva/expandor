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
        $platformName = $brand->name() !== '' ? $brand->name() : 'Expandor';
        $hasLogo = filled($brand->logo());
        $expireMinutes = $expireMinutes ?? (int) config('auth.passwords.users.expire', 60);
    @endphp
    <title>Nova senha — {{ $platformName }}</title>
    @if($brand->faviconUrl)
        <link rel="icon" href="{{ $brand->faviconUrl }}">
    @endif
    <style>
        :root {
            {{ $themeService->cssVariables($brand) }}
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.25rem;
            font-family: {{ $themeService->fontFamily($brand) }};
            background: {{ $brand->loginImageUrl
                ? "linear-gradient(rgba(15,17,23,0.72), rgba(15,17,23,0.85)), url('{$brand->loginImageUrl}') center/cover no-repeat"
                : $themeService->bodyBackground($brand) }};
            color: var(--text-on-bg);
        }
        .card {
            width: min(440px, 100%);
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1.15rem;
            padding: 2rem 1.75rem 1.75rem;
            text-align: center;
            color: var(--text);
        }
        .brand-hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .brand-hero__logo {
            display: grid;
            place-items: center;
            width: min(100%, 280px);
            min-height: 72px;
        }
        .brand-hero__logo img {
            display: block;
            width: auto;
            max-width: 100%;
            height: auto;
            max-height: 80px;
            object-fit: contain;
        }
        .brand-hero__wordmark {
            margin: 0;
            font-size: clamp(1.6rem, 5vw, 2rem);
            font-weight: 800;
            letter-spacing: 0.04em;
            line-height: 1.1;
            font-family: {{ $themeService->headingFontFamily($brand) }};
            color: var(--text);
        }
        .brand-hero__welcome {
            margin: 0;
            color: var(--text);
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1.3;
            font-family: {{ $themeService->headingFontFamily($brand) }};
        }
        .brand-hero__subtitle {
            margin: 0.05rem 0 0;
            max-width: 22rem;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.45;
        }
        form { text-align: left; margin-top: 1.35rem; }
        label {
            display: block;
            margin-bottom: 0.35rem;
            color: var(--muted);
            font-size: 0.9rem;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            margin-bottom: 1rem;
            background: color-mix(in srgb, var(--bg-elevated) 88%, var(--text) 12%);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 0.65rem;
            padding: 0.8rem 0.9rem;
            font-size: 1rem;
        }
        input:focus {
            outline: 2px solid color-mix(in srgb, var(--accent) 55%, transparent);
            outline-offset: 1px;
            border-color: var(--accent);
        }
        .hint {
            margin: -0.35rem 0 1rem;
            color: var(--muted);
            font-size: 0.82rem;
            line-height: 1.4;
        }
        .btn-primary {
            display: block;
            width: 100%;
            border: 0;
            border-radius: 0.65rem;
            padding: 0.9rem 1rem;
            background: var(--accent);
            color: var(--button-text);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }
        .error {
            color: color-mix(in srgb, var(--accent-2, #EF4444) 75%, var(--text) 25%);
            background: color-mix(in srgb, var(--accent-2, #EF4444) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent-2, #EF4444) 35%, var(--border));
            border-radius: 0.65rem;
            margin: 0 0 1rem;
            padding: 0.75rem 0.85rem;
            font-size: 0.9rem;
            text-align: left;
        }
        .back-link {
            display: block;
            margin-top: 1.15rem;
            color: var(--muted);
            font-size: 0.92rem;
            text-decoration: underline;
            text-underline-offset: 0.15em;
        }
        @media (max-width: 480px) {
            body { padding: 1rem; }
            .card { padding: 1.5rem 1.15rem 1.35rem; border-radius: 1rem; }
        }
    </style>
    @if($brand->customCss)
        <style>{!! $brand->customCss !!}</style>
    @endif
</head>
<body>
<div class="card {{ $themeService->contrastClass($brand) }}" data-reset-password-form="1">
    <header class="brand-hero">
        @if($hasLogo)
            <div class="brand-hero__logo">
                <img src="{{ $brand->logo() }}" alt="{{ $platformName }}" data-platform-logo="1">
            </div>
        @else
            <h1 class="brand-hero__wordmark" data-platform-fallback="1">{{ $platformName }}</h1>
        @endif
        <p class="brand-hero__welcome">Nova senha</p>
        <p class="brand-hero__subtitle">Defina uma senha segura para continuar.</p>
    </header>

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">E-mail</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email', $email) }}"
            required
            autocomplete="username"
        >

        <label for="password">Nova senha</label>
        <input
            id="password"
            type="password"
            name="password"
            required
            autofocus
            autocomplete="new-password"
        >
        <p class="hint" data-password-requirements="1">
            Mínimo de 8 caracteres. Confirme a senha abaixo. O link expira em {{ $expireMinutes }} minutos.
        </p>

        <label for="password_confirmation">Confirmar nova senha</label>
        <input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            required
            autocomplete="new-password"
        >

        <button class="btn-primary" type="submit">Redefinir senha</button>
    </form>

    <a class="back-link" href="{{ route('login') }}">Voltar ao login</a>
</div>
</body>
</html>
