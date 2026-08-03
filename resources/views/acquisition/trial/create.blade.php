<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $brand = $brand ?? app(\App\Domains\Branding\Services\BrandingService::class)->resolveForRequest();
        $themeService = app(\App\Domains\Branding\Services\ThemeService::class);
        $platformName = $brand->name() !== '' ? $brand->name() : 'Expandor';
        $hasLogo = filled($brand->logo());
        $demoChoice = old('with_demo_data');
        if ($demoChoice === null) {
            $demoChoice = '1';
        } else {
            $demoChoice = $demoChoice ? '1' : '0';
        }
    @endphp
    <title>Cadastro — {{ $platformName }}</title>
    @if($brand->faviconUrl)
        <link rel="icon" href="{{ $brand->faviconUrl }}">
    @endif
    <style>
        :root { {{ $themeService->cssVariables($brand) }} }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.25rem;
            font-family: {{ $themeService->fontFamily($brand) }};
            background: {{ $themeService->bodyBackground($brand) }};
            color: var(--text-on-bg);
        }
        .card {
            width: min(520px, 100%);
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1.15rem;
            padding: 1.75rem;
            color: var(--text);
        }
        .brand {
            display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem;
        }
        .brand img { max-height: 40px; max-width: 140px; object-fit: contain; }
        .brand strong { font-size: 1.05rem; }
        h1 { margin: 0 0 .35rem; font-size: 1.45rem; font-family: {{ $themeService->headingFontFamily($brand) }}; }
        .meta { color: var(--muted); margin: 0 0 1.25rem; font-size: .92rem; line-height: 1.45; }
        label { display: block; margin: .65rem 0 .3rem; color: var(--muted); font-size: .9rem; }
        input, select {
            width: 100%; background: color-mix(in srgb, var(--bg-elevated) 88%, var(--text) 12%);
            border: 1px solid var(--border); color: var(--text);
            border-radius: .65rem; padding: .75rem .85rem; font-size: 1rem;
        }
        input:focus, select:focus {
            outline: 2px solid color-mix(in srgb, var(--accent) 55%, transparent);
            border-color: var(--accent);
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        @media (max-width: 560px) {
            body { padding: 1rem; }
            .card { padding: 1.35rem 1.1rem; }
            .grid-2 { grid-template-columns: 1fr; }
        }
        .check, .radio {
            display: flex; gap: .55rem; align-items: flex-start; margin: .65rem 0;
            color: var(--muted); font-size: .9rem; text-align: left;
        }
        .check input, .radio input { width: auto; margin-top: .2rem; }
        .choice-box {
            margin: 1rem 0 1.15rem; padding: .85rem 1rem;
            border: 1px solid var(--border); border-radius: .75rem;
            background: color-mix(in srgb, var(--bg-elevated) 92%, var(--text) 8%);
        }
        .choice-box > p { margin: 0 0 .55rem; color: var(--text); font-weight: 650; font-size: .95rem; }
        button[type="submit"] {
            width: 100%; border: 0; border-radius: .65rem; padding: .95rem 1rem;
            background: var(--accent); color: var(--button-text);
            font-weight: 750; cursor: pointer; font-size: 1rem;
        }
        button[type="submit"]:hover { filter: brightness(1.06); }
        .error {
            color: color-mix(in srgb, var(--accent-2, #EF4444) 75%, var(--text) 25%);
            background: color-mix(in srgb, var(--accent-2, #EF4444) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent-2, #EF4444) 35%, var(--border));
            border-radius: .65rem; margin-bottom: 1rem; padding: .75rem .85rem; font-size: .9rem;
        }
        .back { display: inline-block; margin-bottom: 1rem; color: var(--muted); text-decoration: none; font-size: .9rem; }
        .hp { position: absolute; left: -10000px; opacity: 0; height: 0; width: 0; overflow: hidden; }
        .section { margin-top: 1rem; padding-top: .75rem; border-top: 1px solid var(--border); }
        .section-title { margin: 0; font-size: .85rem; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
    </style>
</head>
<body>
<div class="card {{ $themeService->contrastClass($brand) }}" data-trial-signup="1">
    <a class="back" href="{{ route('login') }}">← Voltar ao login</a>

    <div class="brand">
        @if($hasLogo)
            <img src="{{ $brand->logo() }}" alt="{{ $platformName }}" data-platform-logo="1">
        @endif
        <strong>{{ $platformName }}</strong>
    </div>

    <h1>Criar minha conta grátis</h1>
    <p class="meta">
        {{ $trialDays }} dias no plano {{ $planName }} — sem cartão.
        Depois você completa o Setup Wizard e libera o ambiente.
    </p>

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('signup.store') }}" data-signup-form="1">
        @csrf

        <div class="hp" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <p class="section-title">Empresa</p>
        <label for="company_name">Nome da empresa</label>
        <input id="company_name" name="company_name" value="{{ old('company_name') }}" required maxlength="255" autofocus>

        <label for="segment">Segmento</label>
        <select id="segment" name="segment" required>
            <option value="">Selecione…</option>
            @foreach($segments as $value => $label)
                <option value="{{ $value }}" @selected(old('segment') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <div class="section">
            <p class="section-title">Administrador</p>
            <label for="admin_name">Nome</label>
            <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required maxlength="255">

            <label for="admin_email">E-mail</label>
            <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" required maxlength="255" autocomplete="username">

            <label for="admin_whatsapp">WhatsApp / telefone</label>
            <input id="admin_whatsapp" name="admin_whatsapp" value="{{ old('admin_whatsapp') }}" required maxlength="40" placeholder="11999999999" autocomplete="tel">

            <div class="grid-2">
                <div>
                    <label for="admin_password">Senha</label>
                    <input id="admin_password" type="password" name="admin_password" required autocomplete="new-password">
                </div>
                <div>
                    <label for="admin_password_confirmation">Confirmar senha</label>
                    <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" required autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="section">
            <div class="choice-box">
                <p>Quer conhecer a plataforma com dados de exemplo?</p>
                <label class="radio">
                    <input type="radio" name="with_demo_data" value="0" @checked($demoChoice === '0')>
                    <span><strong>Começar vazio</strong> — ambiente limpo para configurar do zero.</span>
                </label>
                <label class="radio">
                    <input type="radio" name="with_demo_data" value="1" @checked($demoChoice === '1')>
                    <span><strong>Criar ambiente demonstração</strong> — pontos, visitas, clientes, produtos e vendedores de exemplo.</span>
                </label>
            </div>

            <label class="check">
                <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted')) required>
                <span>Aceito os termos de uso</span>
            </label>
        </div>

        <button type="submit">Criar minha conta grátis</button>
    </form>
</div>
</body>
</html>
