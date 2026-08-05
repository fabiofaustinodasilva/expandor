@extends('layouts.platform')

@section('title', 'Site — Campanhas')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.dashboard') }}" class="header-meta" style="text-decoration:none;">← Dashboard</a>
    </div>

    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">Campanhas UTM</h1>
        <div class="header-meta">Cadastre campanhas para rastrear origem de tráfego e leads.</div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <strong>Dica:</strong>
        <span class="header-meta" style="display:block;margin-top:0.35rem;">
            Os parâmetros <code>utm_source</code>, <code>utm_medium</code> e <code>utm_campaign</code> na URL devem corresponder aos valores cadastrados aqui para atribuição automática.
            Exemplo: <code>?utm_source=google&amp;utm_medium=cpc&amp;utm_campaign=crm-brasil</code>
        </span>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Nova campanha</h2>
        <form method="POST" action="{{ route('platform.marketplace.campaigns.store') }}">
            @csrf

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="name">Nome *</label>
                    <input class="form-control" id="name" name="name" required maxlength="180"
                           value="{{ old('name') }}">
                </div>
                <div class="form-group">
                    <label for="source">Source (utm_source)</label>
                    <input class="form-control" id="source" name="source" maxlength="120"
                           value="{{ old('source') }}" placeholder="google">
                </div>
                <div class="form-group">
                    <label for="medium">Medium (utm_medium)</label>
                    <input class="form-control" id="medium" name="medium" maxlength="120"
                           value="{{ old('medium') }}" placeholder="cpc">
                </div>
                <div class="form-group">
                    <label for="campaign">Campaign (utm_campaign)</label>
                    <input class="form-control" id="campaign" name="campaign" maxlength="180"
                           value="{{ old('campaign') }}" placeholder="crm-brasil">
                </div>
                <div class="form-group">
                    <label for="investment">Investimento (R$)</label>
                    <input class="form-control" id="investment" name="investment" type="number" min="0" step="0.01"
                           value="{{ old('investment', 0) }}">
                </div>
            </div>

            <div class="form-group">
                <label style="display:inline-flex;gap:.45rem;align-items:center;">
                    <input type="checkbox" name="active" value="1" @checked(old('active', true))>
                    Ativa
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Criar campanha</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Campanhas cadastradas</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Source</th>
                <th>Medium</th>
                <th>Campaign</th>
                <th>Investimento</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($campaigns as $campaign)
                <tr>
                    <td>{{ $campaign->name }}</td>
                    <td>{{ $campaign->source ?: '—' }}</td>
                    <td>{{ $campaign->medium ?: '—' }}</td>
                    <td>{{ $campaign->campaign ?: '—' }}</td>
                    <td>R$ {{ number_format((float) $campaign->investment, 2, ',', '.') }}</td>
                    <td>
                        @if($campaign->active)
                            <span class="badge" style="color:var(--success);">Ativa</span>
                        @else
                            <span class="badge">Inativa</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('platform.marketplace.campaigns.destroy', $campaign) }}"
                              onsubmit="return confirm('Remover esta campanha?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhuma campanha cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
