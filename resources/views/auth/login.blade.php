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
        $platformSlogan = $brand->sloganText();
        $hasLogo = filled($brand->logo());
        $showTrialCta = auth()->guest();
    @endphp
    <title>Login — {{ $platformName }}</title>
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
            min-height: 88px;
        }
        .brand-hero__logo img {
            display: block;
            width: auto;
            max-width: 100%;
            height: auto;
            max-height: 96px;
            object-fit: contain;
        }
        .brand-hero__wordmark {
            margin: 0;
            font-size: clamp(1.85rem, 5vw, 2.25rem);
            font-weight: 800;
            letter-spacing: 0.04em;
            line-height: 1.1;
            font-family: {{ $themeService->headingFontFamily($brand) }};
            color: var(--text);
        }
        .brand-hero__slogan {
            margin: 0;
            max-width: 22rem;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            line-height: 1.45;
            text-transform: uppercase;
            color: var(--muted);
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
        form {
            text-align: left;
            margin-top: 1.35rem;
        }
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
        input::placeholder {
            color: var(--muted);
            opacity: 0.9;
        }
        input:focus {
            outline: 2px solid color-mix(in srgb, var(--accent) 55%, transparent);
            outline-offset: 1px;
            border-color: var(--accent);
        }
        .remember {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 1.15rem;
            color: var(--muted);
            font-size: 0.9rem;
        }
        .remember input {
            width: auto;
            margin: 0;
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
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            filter: brightness(1.06);
        }
        .btn-trial {
            display: block;
            width: 100%;
            box-sizing: border-box;
            border: 0;
            border-radius: 0.65rem;
            padding: 0.95rem 1rem;
            background: color-mix(in srgb, var(--accent) 88%, var(--accent-2, #EF4444) 12%);
            color: var(--button-text);
            font-weight: 750;
            font-size: 1rem;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 35%, transparent);
        }
        .btn-trial:hover {
            filter: brightness(1.07);
        }
        .auth-divider {
            display: block;
            height: 1px;
            margin: 1.35rem 0 1.15rem;
            background: var(--border);
            border: 0;
        }
        .trial-block {
            text-align: center;
        }
        .trial-block__label {
            margin: 0 0 0.75rem;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.4;
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
        .support {
            margin-top: 1.15rem;
            color: var(--muted);
            font-size: 0.85rem;
        }
        @media (max-width: 480px) {
            body { padding: 1rem; }
            .card { padding: 1.5rem 1.15rem 1.35rem; border-radius: 1rem; }
            .brand-hero { gap: 0.65rem; margin-bottom: 1.05rem; }
            .brand-hero__logo { min-height: 72px; width: min(100%, 220px); }
            .brand-hero__logo img { max-height: 72px; }
            .brand-hero__wordmark { font-size: 1.7rem; }
            .brand-hero__slogan { font-size: 0.72rem; max-width: 18rem; }
            .brand-hero__welcome { font-size: 1.1rem; }
            .brand-hero__subtitle { font-size: 0.88rem; max-width: 100%; }
            .auth-divider { margin: 1.2rem 0 1rem; }
            .btn-trial { padding: 0.95rem 0.85rem; }
        }
    </style>
    @if($brand->customCss)
        <style>{!! $brand->customCss !!}</style>
    @endif
</head>
<body>
<div class="card {{ $themeService->contrastClass($brand) }}">
    <header class="brand-hero">
        @if($hasLogo)
            <div class="brand-hero__logo">
                <img
                    src="{{ $brand->logo() }}"
                    alt="{{ $platformName }}"
                    data-platform-logo="1"
                >
            </div>
        @else
            <h1 class="brand-hero__wordmark" data-platform-fallback="1">{{ $platformName }}</h1>
        @endif
        @if($platformSlogan)
            <p class="brand-hero__slogan" data-platform-slogan="1">{{ $platformSlogan }}</p>
        @endif
        <p class="brand-hero__welcome">Bem-vindo ao {{ $platformName }}</p>
        <p class="brand-hero__subtitle">Acesse sua conta</p>
    </header>

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">Usuário</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

        <label for="password">Senha</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">

        <label class="remember">
            <input type="checkbox" name="remember" value="1">
            Lembrar-me
        </label>

        <button class="btn-primary" type="submit">Entrar</button>
    </form>

    @if($showTrialCta)
        <hr class="auth-divider" aria-hidden="true">

        <section class="trial-block" aria-label="Novos visitantes" data-trial-cta="1">
            <p class="trial-block__label">Ainda não conhece o {{ $platformName }}?</p>
            <a
                class="btn-trial"
                href="{{ route('signup.create') }}"
                data-trial-link="1"
            >
                Começar teste grátis
            </a>
        </section>
    @endif

    @if($brand->supportEmail || $brand->supportPhone)
        <div class="support">
            Suporte:
            @if($brand->supportEmail) {{ $brand->supportEmail }} @endif
            @if($brand->supportEmail && $brand->supportPhone) · @endif
            @if($brand->supportPhone) {{ $brand->supportPhone }} @endif
        </div>
    @endif
</div>
</body>
</html>
