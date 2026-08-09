@extends('layouts.operational')

@section('title', 'Google Maps')

@section('page')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Google Maps</h1>
            <p class="header-meta">Integração tenant — credenciais da sua empresa.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('operations.integrations') }}">Voltar</a>
    </div>

    @if(session('success'))
        <div class="card" style="margin-bottom:1rem; border-color:#86efac;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="card" style="margin-bottom:1rem; border-color:#fca5a5;">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="card" style="margin-bottom:1rem; border-color:#fca5a5;">
            <ul style="margin:0; padding-left:1.1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-bottom:1rem;">
        <p class="header-meta" style="margin:0;">{{ $billingNotice }}</p>
    </div>

    @if(!$entitled)
        <div class="card">
            <strong>Disponível em plano superior</strong>
            <p class="header-meta">Seu plano atual não inclui Google Maps. O mapa padrão Expandor continua funcionando.</p>
            <a class="btn btn-primary" href="{{ route('company.plan.show') }}">Ver planos</a>
        </div>
    @else
        <div class="card" style="margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                <div>
                    <div><strong>Status do plano:</strong> ✓ Disponível</div>
                    <div class="header-meta" style="margin-top:.35rem;">
                        @if($integration?->isUsable())
                            ● Conectado
                        @elseif($integration?->status?->value === 'error')
                            ● Erro — {{ $integration->last_error }}
                        @else
                            ● Não conectado
                        @endif
                    </div>
                    @if($maskedKey)
                        <div class="header-meta" style="margin-top:.5rem;">API Key: <code>{{ $maskedKey }}</code></div>
                    @endif
                    @if($integration?->last_tested_at)
                        <div class="header-meta">Último teste: {{ $integration->last_tested_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>
        </div>

        @if($canManage)
            <div class="card">
                {{--
                  Shared form for Save + Test.
                  Do NOT emit a hidden Laravel method-spoof field for PUT on this form:
                  it would convert the Test submit into PUT /google-maps/test → 405
                  (route is POST-only). Save carries _method=PUT on its own submit button;
                  Test is a plain POST via formaction/formmethod.
                --}}
                <form method="post" action="{{ route('operations.integrations.google-maps.update') }}" style="display:grid; gap:1rem;">
                    @csrf

                    <label>
                        <span>API Key Web</span>
                        <input
                            type="password"
                            name="browser_api_key"
                            autocomplete="off"
                            placeholder="{{ $maskedKey ? 'Deixe em branco no teste para usar a chave salva, ou informe nova chave' : 'Cole a API Key do Google Cloud (Maps JavaScript)' }}"
                            style="width:100%;"
                        >
                    </label>

                    <div class="header-meta" style="margin:0; display:grid; gap:.45rem;">
                        <p style="margin:0;">Use uma <strong>API Key de navegador</strong> com restrição por HTTP referrers (não use chave de servidor).</p>
                        <p style="margin:0;">Ex.: <code>https://seu-dominio/*</code> e, em local, <code>http://localhost/*</code> / <code>http://127.0.0.1/*</code>.</p>
                        <p style="margin:0;">No Google Cloud, habilite ao menos <strong>Maps JavaScript API</strong> (mapa visual). Billing e quotas são da conta Google da empresa.</p>
                        <p style="margin:0;">“Testar conexão” valida a chave via Geocoding no servidor. Chaves só com restrição de referrer podem falhar nesse teste e ainda assim funcionar no mapa do navegador — confirme no mapa após salvar.</p>
                    </div>

                    <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary" name="_method" value="PUT">Salvar e ativar</button>
                        <button
                            type="submit"
                            class="btn btn-ghost"
                            formaction="{{ route('operations.integrations.google-maps.test') }}"
                            formmethod="post"
                        >Testar conexão</button>
                    </div>
                </form>

                @if($integration && $integration->hasBrowserApiKey())
                    <form method="post" action="{{ route('operations.integrations.google-maps.disconnect') }}" style="margin-top:1.25rem;" onsubmit="return confirm('Desconectar Google Maps? O mapa padrão Expandor será usado.');">
                        @csrf
                        <button type="submit" class="btn btn-ghost">Desconectar Google Maps</button>
                    </form>
                @endif
            </div>
        @else
            <div class="card">
                <p class="header-meta">Você pode visualizar o status, mas não possui permissão para alterar credenciais.</p>
            </div>
        @endif
    @endif
@endsection
