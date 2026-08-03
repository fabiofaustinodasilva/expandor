@extends('layouts.app')

@section('title', 'Oportunidades')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Oportunidades</h1>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('crm.opportunities.kanban') }}">Kanban</a>
            @can('create', App\Domains\CRM\Models\Opportunity::class)
                <a class="btn btn-primary" href="{{ route('crm.opportunities.create') }}">Nova</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Título</th>
                <th>Estágio</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Dono</th>
                <th>Lead</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($opportunities as $opportunity)
                <tr>
                    <td>{{ $opportunity->title }}</td>
                    <td>{{ $opportunity->stage?->name }}</td>
                    <td>R$ {{ number_format((float) $opportunity->amount, 2, ',', '.') }}</td>
                    <td><span class="badge">{{ $opportunity->status?->label() }}</span></td>
                    <td>{{ $opportunity->owner?->name ?: '—' }}</td>
                    <td>{{ $opportunity->lead?->name ?: '—' }}</td>
                    <td class="actions">
                        @can('update', $opportunity)
                            <a class="btn btn-ghost" href="{{ route('crm.opportunities.edit', $opportunity) }}">Editar</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhuma oportunidade.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $opportunities->links() }}</div>
    </div>
@endsection
