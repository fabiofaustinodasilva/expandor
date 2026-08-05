@extends('layouts.platform')

@section('title', 'Site — Leads')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.dashboard') }}" class="header-meta" style="text-decoration:none;">← Dashboard</a>
    </div>

    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">Leads do site</h1>
        <div class="header-meta">Pedidos de demonstração e contatos capturados na landing.</div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Empresa</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Segmento</th>
                <th>UTM Source</th>
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
                    <td>{{ $lead->email }}</td>
                    <td>{{ $lead->phone ?: '—' }}</td>
                    <td>{{ $lead->segment ?: '—' }}</td>
                    <td>{{ $lead->utm_source ?: '—' }}</td>
                    <td>
                        <span class="badge">{{ $lead->status->label() }}</span>
                    </td>
                    <td>{{ $lead->created_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <form method="POST" action="{{ route('platform.marketplace.leads.update', $lead) }}" style="min-width:14rem;">
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
                <tr><td colspan="9">Nenhum lead capturado ainda.</td></tr>
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
