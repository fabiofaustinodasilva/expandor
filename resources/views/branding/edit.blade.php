@extends('layouts.operational')

@section('title', 'Branding')

@section('page')
    @php
        $colors = app(\App\Domains\Branding\Services\ThemeService::class)->normalizeColors($payload->colors);
        $fonts = $payload->fonts;
        $socials = $payload->socials;
        $isUpdate = $brand !== null;
    @endphp

    <div style="margin-bottom:1rem;">
        <a href="{{ route('operations.settings') }}" class="header-meta" style="text-decoration:none;">← Configurações</a>
    </div>

    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Branding e White Label</h1>
            <div class="header-meta">Identidade visual de {{ $company->name }}</div>
        </div>
        @if(auth()->user()?->hasPermission('company.manage'))
            <a class="btn btn-ghost" href="{{ route('company.show', $company) }}">Dados da empresa</a>
        @endif
    </div>

    @if($errors->any())
        <x-ux.alert type="error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </x-ux.alert>
    @endif

    <form
        method="POST"
        action="{{ $isUpdate ? route('company.branding.update') : route('company.branding.store') }}"
        enctype="multipart/form-data"
        id="branding-form"
    >
        @csrf
        @if($isUpdate)
            @method('PUT')
        @endif

        <div class="grid grid-2">
            <div class="card">
                <h2 style="margin-top:0;">Identidade</h2>

                <div class="form-group">
                    <label for="system_name">Nome do sistema</label>
                    <input class="form-control" id="system_name" name="system_name" type="text"
                           value="{{ old('system_name', $payload->systemName) }}" required data-preview="system-name">
                </div>

                <div class="form-group">
                    <label for="display_name">Nome de exibição</label>
                    <input class="form-control" id="display_name" name="display_name" type="text"
                           value="{{ old('display_name', $payload->displayName) }}" required data-preview="display-name">
                </div>

                <div class="form-group">
                    <label for="slogan">Slogan / tagline (opcional)</label>
                    <input class="form-control" id="slogan" name="slogan" type="text"
                           value="{{ old('slogan', $payload->sloganText()) }}" maxlength="255"
                           placeholder="Opcional — não repetir o nome da marca">
                    <div class="header-meta" style="margin-top:.35rem;">Campo separado do nome. Se vazio, não aparece na interface.</div>
                </div>

                <div class="form-group">
                    <label for="theme">Tema</label>
                    <select class="form-control" id="theme" name="theme" data-preview="theme">
                        @foreach(\App\Domains\Branding\Enums\BrandTheme::cases() as $theme)
                            <option value="{{ $theme->value }}" @selected(old('theme', $payload->theme->value) === $theme->value)>
                                {{ $theme->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-media-upload
                    name="logo"
                    label="Logo principal"
                    :current-url="$payload->logoUrl"
                    remove-name="remove_logo"
                    accept="image/jpeg,image/png,image/webp,image/svg+xml"
                    hint="JPG, PNG, WEBP ou SVG até 5MB. Otimizado automaticamente."
                />
                <x-media-upload
                    name="logo_mark"
                    label="Logo reduzido"
                    :current-url="$payload->logoMarkUrl"
                    remove-name="remove_logo_mark"
                    accept="image/jpeg,image/png,image/webp,image/svg+xml"
                    hint="Usado no menu lateral. JPG, PNG, WEBP ou SVG até 5MB."
                />
                <x-media-upload
                    name="favicon"
                    label="Favicon"
                    :current-url="$payload->faviconUrl"
                    remove-name="remove_favicon"
                    accept="image/png,image/x-icon,image/webp,.ico"
                    hint="PNG, WEBP ou ICO até 5MB."
                    preview-height="48px"
                />
                <x-media-upload
                    name="login_image"
                    label="Imagem da tela de login"
                    :current-url="$payload->loginImageUrl"
                    remove-name="remove_login_image"
                    accept="image/jpeg,image/png,image/webp"
                    hint="JPG, PNG ou WEBP até 5MB."
                    preview-height="96px"
                />

                <h2>Cores</h2>
                <p class="header-meta" style="margin-top:-.4rem;">Primária, secundária e destaque alimentam botões e cards em todo o produto.</p>
                <div class="grid grid-2">
                    @foreach([
                        'primary' => 'Cor primária',
                        'secondary' => 'Cor secundária',
                        'highlight' => 'Cor de destaque',
                        'bg' => 'Fundo',
                        'bg_elevated' => 'Superfície',
                        'bg_soft' => 'Superfície suave',
                        'border' => 'Borda',
                        'text' => 'Texto',
                        'muted' => 'Texto secundário',
                        'success' => 'Sucesso',
                        'warning' => 'Aviso',
                    ] as $key => $label)
                        <div class="form-group">
                            <label for="color_{{ $key }}">{{ $label }}</label>
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <input type="color" id="color_{{ $key }}_picker"
                                       value="{{ old('colors.'.$key, $colors[$key] ?? '#000000') }}"
                                       data-sync-text="color_{{ $key }}"
                                       style="width:3rem; height:2.5rem; padding:0; border:0; background:transparent;">
                                <input class="form-control" id="color_{{ $key }}" name="colors[{{ $key }}]" type="text"
                                       value="{{ old('colors.'.$key, $colors[$key] ?? '') }}"
                                       data-preview-color="{{ $key }}"
                                       data-sync-picker="color_{{ $key }}_picker">
                            </div>
                        </div>
                    @endforeach
                </div>

                <h2>Fontes</h2>
                <div class="form-group">
                    <label for="fonts_family">Família principal</label>
                    <input class="form-control" id="fonts_family" name="fonts[family]" type="text"
                           value="{{ old('fonts.family', $fonts['family'] ?? '') }}" data-preview="font-family">
                </div>
                <div class="form-group">
                    <label for="fonts_heading">Família de títulos (opcional)</label>
                    <input class="form-control" id="fonts_heading" name="fonts[heading]" type="text"
                           value="{{ old('fonts.heading', $fonts['heading'] ?? '') }}">
                </div>

                <h2>Suporte</h2>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="support_email">E-mail de suporte</label>
                        <input class="form-control" id="support_email" name="support_email" type="email"
                               value="{{ old('support_email', $payload->supportEmail) }}">
                    </div>
                    <div class="form-group">
                        <label for="support_phone">Telefone de suporte</label>
                        <input class="form-control" id="support_phone" name="support_phone" type="text"
                               value="{{ old('support_phone', $payload->supportPhone) }}">
                    </div>
                </div>

                <h2>Redes sociais</h2>
                <div class="grid grid-2">
                    @foreach(['facebook','instagram','linkedin','twitter','youtube','whatsapp'] as $network)
                        <div class="form-group">
                            <label for="social_{{ $network }}">{{ ucfirst($network) }}</label>
                            <input class="form-control" id="social_{{ $network }}" name="socials[{{ $network }}]" type="text"
                                   value="{{ old('socials.'.$network, $socials[$network] ?? '') }}">
                        </div>
                    @endforeach
                </div>

                <h2>Domínio e CSS</h2>
                <div class="form-group">
                    <label for="custom_domain">Domínio personalizado</label>
                    <input class="form-control" id="custom_domain" name="custom_domain" type="text"
                           placeholder="crm.suaempresa.com"
                           value="{{ old('custom_domain', $payload->customDomain) }}">
                    <div class="header-meta" style="margin-top:0.35rem;">
                        Preparado para resolução futura. SSL/DNS automáticos ainda não estão disponíveis.
                    </div>
                </div>
                <div class="form-group">
                    <label for="custom_css">CSS personalizado</label>
                    <textarea class="form-control" id="custom_css" name="custom_css" rows="6"
                              placeholder=".sidebar { ... }">{{ old('custom_css', $payload->customCss) }}</textarea>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">
                        {{ $isUpdate ? 'Salvar branding' : 'Criar branding' }}
                    </button>
                </div>
            </div>

            <div class="card" style="position:sticky; top:1rem; align-self:start;">
                <h2 style="margin-top:0;">Preview em tempo real</h2>
                <div id="brand-preview" style="border:1px solid var(--border); border-radius:0.85rem; overflow:hidden;">
                    <div id="preview-shell" style="background:var(--bg); color:var(--text); padding:1rem;">
                        <div style="display:flex; gap:0.75rem; align-items:center; margin-bottom:1rem;">
                            <img id="preview-logo" src="{{ $payload->logoUrl }}" alt=""
                                 style="height:36px; max-width:120px; object-fit:contain; {{ $payload->logoUrl ? '' : 'display:none;' }}">
                            <div>
                                <div id="preview-system-name" style="font-weight:700;">{{ $payload->systemName }}</div>
                                <div id="preview-display-name" class="header-meta">{{ $payload->displayName }}</div>
                            </div>
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-bottom:1rem;">
                            <button type="button" class="btn btn-primary" id="preview-btn-primary">Ação principal</button>
                            <button type="button" class="btn btn-ghost" id="preview-btn-ghost">Secundário</button>
                        </div>
                        <div style="border:1px dashed var(--border); border-radius:0.75rem; padding:0.85rem; margin-bottom:1rem;">
                            <div class="header-meta">Card de exemplo</div>
                            <div style="font-size:1.1rem; font-weight:700;">Conversão do dia</div>
                            <div class="header-meta">Cores e tipografia aplicadas ao layout.</div>
                        </div>
                        <div id="preview-login" style="border-radius:0.75rem; min-height:120px; background-size:cover; background-position:center; {{ $payload->loginImageUrl ? "background-image:url('{$payload->loginImageUrl}');" : 'background:var(--bg-soft);' }} display:flex; align-items:flex-end;">
                            <div style="width:100%; padding:0.75rem; background:rgba(0,0,0,0.45);">
                                <strong id="preview-login-title">{{ $payload->displayName }}</strong>
                                <div class="header-meta">Tela de login</div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="header-meta" style="margin-top:0.85rem;">
                    O preview reflete as alterações do formulário antes de salvar.
                </p>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const root = document.documentElement;
        const colorMap = {
            bg: '--bg', bg_elevated: '--bg-elevated', bg_soft: '--bg-soft', border: '--border',
            text: '--text', muted: '--muted', primary: '--primary', secondary: '--secondary',
            highlight: '--highlight', accent: '--accent', accent_2: '--accent-2',
            success: '--success', warning: '--warning'
        };

        function applyColor(key, value) {
            const cssVar = colorMap[key];
            if (!cssVar || !value) return;
            root.style.setProperty(cssVar, value);
            if (key === 'primary') root.style.setProperty('--accent', value);
            if (key === 'highlight') root.style.setProperty('--accent-2', value);
        }

        document.querySelectorAll('[data-preview-color]').forEach((input) => {
            const key = input.getAttribute('data-preview-color');
            const pickerId = input.getAttribute('data-sync-picker');
            const picker = pickerId ? document.getElementById(pickerId) : null;
            const sync = () => {
                applyColor(key, input.value);
                if (picker && /^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/.test(input.value)) {
                    picker.value = input.value;
                }
            };
            input.addEventListener('input', sync);
            picker?.addEventListener('input', () => {
                input.value = picker.value;
                applyColor(key, picker.value);
            });
        });

        document.getElementById('system_name')?.addEventListener('input', (e) => {
            document.getElementById('preview-system-name').textContent = e.target.value || 'Sistema';
        });
        document.getElementById('display_name')?.addEventListener('input', (e) => {
            document.getElementById('preview-display-name').textContent = e.target.value || 'Nome de exibição';
            document.getElementById('preview-login-title').textContent = e.target.value || 'Login';
        });
        document.getElementById('logo')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            const img = document.getElementById('preview-logo');
            img.src = URL.createObjectURL(file);
            img.style.display = 'block';
        });
        document.getElementById('login_image')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            document.getElementById('preview-login').style.backgroundImage = "url('" + URL.createObjectURL(file) + "')";
        });
    })();
</script>
@endpush
