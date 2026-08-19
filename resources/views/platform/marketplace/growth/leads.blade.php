@extends('layouts.platform')

@section('title', 'Site — Leads')

@section('content')
    <style>
        .lead-cards { display:none; }
        @media (max-width: 800px) {
            .lead-table { display:none; }
            .lead-cards { display:block; }
            .lead-card { border:1px solid var(--border,#e5e7eb); border-radius:12px; padding:1rem; margin-bottom:.75rem; }
            .lead-card .btn { margin-top:.5rem; margin-right:.35rem; }
        }
    </style>
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.dashboard') }}" class="header-meta" style="text-decoration:none;">← Dashboard</a>
    </div>

    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">Leads do site</h1>
        <div class="header-meta">Pedidos de demonstração e contatos capturados na landing.</div>
    </div>

    <div class="lead-cards">
        @forelse($leads as $lead)
            <article class="lead-card">
                <strong>{{ $lead->name }}</strong>
                <div class="header-meta">{{ $lead->company_name ?: '—' }} · {{ $lead->cityState() }}</div>
                <div class="header-meta">Origem: {{ $lead->origin['origin'] ?? 'Direto' }}</div>
                @if($lead->phone)
                    <div><a href="{{ $lead->tel_url }}">{{ \App\Domains\Marketplace\Growth\Support\BrazilianPhone::format($lead->phone) }}</a></div>
                    <a class="btn btn-primary" href="{{ route('platform.marketplace.leads.whatsapp', $lead) }}">WhatsApp</a>
                @endif
                <a class="btn btn-ghost" href="{{ route('platform.marketplace.leads.show', $lead) }}">Detalhes</a>
            </article>
        @empty
            <p>Nenhum lead capturado ainda.</p>
        @endforelse
    </div>

    <div class="card lead-table">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Empresa</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Origem</th>
                <th>Status</th>
                <th>Criado em</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($leads as $lead)
                <tr>
                    <td>{{ $lead->name }}</td>
                    <td>{{ $lead->company_name ?: '—' }}</td>
                    <td>{{ $lead->email ?: '—' }}</td>
                    <td>
                        @if($lead->phone)
                            <a href="{{ $lead->tel_url }}">{{ \App\Domains\Marketplace\Growth\Support\BrazilianPhone::format($lead->phone) }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $lead->origin['origin'] ?? ($lead->utm_source ?: '—') }}</td>
                    <td>
                        <span class="badge">{{ $lead->status->label() }}</span>
                    </td>
                    <td>{{ $lead->created_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <a class="btn btn-ghost" href="{{ route('platform.marketplace.leads.show', $lead) }}">Detalhes</a>
                        @if($lead->phone)
                            <a class="btn btn-primary" href="{{ route('platform.marketplace.leads.whatsapp', $lead) }}">WhatsApp</a>
                        @endif
                        <form method="POST" action="{{ route('platform.marketplace.leads.update', $lead) }}" style="min-width:14rem;margin-top:.5rem;">
                            @csrf
                            @method('PUT')
                            <div class="form-group" style="margin-bottom:0.5rem;">
                                <select class="form-control" name="status">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($lead->status === $status)>
                                            {{ $status->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0.5rem;">
                                <textarea class="form-control" name="notes" rows="2" placeholder="Notas internas">{{ old('notes', $lead->notes) }}</textarea>
                            </div>
                            <button class="btn btn-primary" type="submit" style="width:100%;">Salvar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Nenhum lead capturado ainda.</td></tr>
            @endforelse
            </tbody>
        </table>

        @if($leads->hasPages())
            <div style="margin-top:1rem;">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
@endsection
