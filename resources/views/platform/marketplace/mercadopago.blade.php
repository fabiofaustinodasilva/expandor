@extends('layouts.platform')

@section('title', 'Mercado Pago')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.marketplace.settings.edit') }}" class="header-meta" style="text-decoration:none;">← Geral & Landing</a>
    </div>

    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; flex-wrap:wrap; align-items:flex-start;">
        <div>
            <h1 class="page-title" style="margin:0;">Mercado Pago</h1>
            <div class="header-meta">Configure sandbox ou produção sem editar arquivos .env.</div>
        </div>
        <form method="POST" action="{{ route('platform.marketplace.mercadopago.test') }}">
            @csrf
            <button type="submit" class="btn btn-ghost">Testar conexão</button>
        </form>
    </div>

    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('platform.marketplace.mercadopago.update') }}" class="card">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label style="display:inline-flex; gap:.45rem; align-items:center;">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $settings->active))>
                Ativo
            </label>
        </div>

        <div class="form-group">
            <label for="mode">Modo</label>
            <select class="form-control" id="mode" name="mode">
                <option value="sandbox" @selected(old('mode', $settings->mode) === 'sandbox')>Sandbox</option>
                <option value="production" @selected(old('mode', $settings->mode) === 'production')>Produção</option>
            </select>
        </div>

        <div class="form-group">
            <label for="public_key">Public Key</label>
            <input class="form-control" id="public_key" name="public_key" type="text"
                   value="{{ old('public_key', $settings->public_key) }}">
        </div>

        <div class="form-group">
            <label for="access_token">Access Token</label>
            <input class="form-control" id="access_token" name="access_token" type="password"
                   value="" placeholder="{{ filled($settings->access_token) ? '•••••••• (já configurado — deixe em branco para manter)' : 'Informe o token' }}">
        </div>

        <div class="form-group">
            <label for="webhook_url">Webhook URL</label>
            <input class="form-control" id="webhook_url" name="webhook_url" type="text"
                   value="{{ old('webhook_url', $settings->webhook_url ?: url('/webhooks/mercadopago')) }}">
        </div>

        <div class="form-group">
            <label for="webhook_secret">Webhook Secret</label>
            <input class="form-control" id="webhook_secret" name="webhook_secret" type="password"
                   value="" placeholder="{{ filled($settings->webhook_secret) ? '•••••••• (já configurado)' : 'Opcional' }}">
        </div>

        @if($settings->last_tested_at)
            <div class="header-meta" style="margin-bottom:1rem;">
                Último teste: {{ $settings->last_tested_at->format('d/m/Y H:i') }} —
                <strong>{{ $settings->last_test_status }}</strong>
                @if($settings->last_test_message)
                    — {{ $settings->last_test_message }}
                @endif
            </div>
        @endif

        <div class="actions">
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
