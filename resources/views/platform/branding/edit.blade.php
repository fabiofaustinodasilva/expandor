@extends('layouts.platform')

@section('title', 'Branding da Plataforma')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.dashboard') }}" class="header-meta" style="text-decoration:none;">← Dashboard</a>
    </div>

    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">Identidade da Plataforma</h1>
        <div class="header-meta">Logo e cores do Expandor — usadas na tela de login (antes da autenticação).</div>
    </div>

    <form method="POST" action="{{ route('platform.branding.update') }}" enctype="multipart/form-data" class="grid grid-2">
        @csrf
        @method('PUT')

        <div class="card">
            <h2 style="margin-top:0;">Marca Expandor</h2>

            <div class="form-group">
                <label for="name">Nome da plataforma</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $row?->name ?? $payload->name()) }}" required maxlength="120"
                       placeholder="Expandor">
                <div class="header-meta" style="margin-top:.35rem;">Usado em “Bem-vindo ao …”. Não use o slogan aqui.</div>
            </div>

            <div class="form-group">
                <label for="slogan">Slogan / tagline (opcional)</label>
                <input class="form-control" id="slogan" name="slogan" value="{{ old('slogan', $row?->slogan ?? $payload->sloganText()) }}" maxlength="255"
                       placeholder="MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA">
                <div class="header-meta" style="margin-top:.35rem;">Exibido abaixo do logo. Se vazio, o espaço some.</div>
            </div>

            <x-media-upload
                name="logo"
                label="Logo principal"
                :current-url="$payload->logoUrl"
                remove-name="remove_logo"
                accept="image/jpeg,image/png,image/webp"
                hint="PNG, JPG, JPEG ou WEBP até 5MB. Aparece no login."
            />

            <x-media-upload
                name="logo_small"
                label="Logo reduzida"
                :current-url="$payload->logoMarkUrl"
                remove-name="remove_logo_small"
                accept="image/jpeg,image/png,image/webp"
                hint="Favicon e telas pequenas. PNG, JPG, JPEG ou WEBP até 5MB."
                preview-height="48px"
            />

            <x-media-upload
                name="favicon"
                label="Favicon (opcional)"
                :current-url="$payload->faviconUrl"
                remove-name="remove_favicon"
                accept="image/png,image/x-icon,image/webp,.ico"
                hint="Se vazio, usa a logo reduzida."
                preview-height="40px"
            />

            <h2>Cores globais (login)</h2>
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="primary_color">Cor primária</label>
                    <input class="form-control" id="primary_color" name="primary_color" type="text"
                           value="{{ old('primary_color', $row?->colors['primary'] ?? $payload->primaryColor()) }}">
                </div>
                <div class="form-group">
                    <label for="secondary_color">Cor secundária</label>
                    <input class="form-control" id="secondary_color" name="secondary_color" type="text"
                           value="{{ old('secondary_color', $row?->colors['secondary'] ?? $payload->secondaryColor()) }}">
                </div>
                <div class="form-group">
                    <label for="highlight_color">Cor de destaque</label>
                    <input class="form-control" id="highlight_color" name="highlight_color" type="text"
                           value="{{ old('highlight_color', $row?->colors['highlight'] ?? $payload->highlightColor()) }}">
                </div>
            </div>

            <div class="actions" style="margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Salvar identidade</button>
            </div>
        </div>

        <div class="card" style="align-self:start;">
            <h2 style="margin-top:0;">Preview do login</h2>
            <div style="border:1px solid var(--border); border-radius:.85rem; padding:1.25rem; background:var(--bg); text-align:center;">
                @if($payload->logo())
                    <img src="{{ $payload->logo() }}" alt="" style="height:48px; max-width:180px; object-fit:contain; margin-bottom:.65rem;">
                @else
                    <div style="font-weight:800; font-size:1.35rem; margin-bottom:.5rem;">{{ $payload->name() }}</div>
                @endif
                @if($payload->sloganText())
                    <div class="header-meta" style="letter-spacing:.04em; text-transform:uppercase; margin-bottom:.45rem;">{{ $payload->sloganText() }}</div>
                @endif
                <div style="font-weight:700; margin-bottom:.25rem;">Bem-vindo ao {{ $payload->name() }}</div>
                <div class="header-meta" style="margin-bottom:.75rem;">Acesse sua conta</div>
                <div style="height:2.4rem; border-radius:.65rem; background:var(--bg-soft); border:1px solid var(--border); margin-bottom:.5rem;"></div>
                <div style="height:2.4rem; border-radius:.65rem; background:var(--bg-soft); border:1px solid var(--border); margin-bottom:.75rem;"></div>
                <div style="height:2.5rem; border-radius:.65rem; background:{{ $payload->primaryColor() }};"></div>
            </div>
            <p class="header-meta" style="margin-top:.85rem;">
                Após o login, cada empresa continua com o próprio branding em `companies/{id}/branding`.
            </p>
        </div>
    </form>
@endsection
