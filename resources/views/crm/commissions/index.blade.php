@extends('layouts.app')

@section('title', 'Comissões')

@section('content')
    <h1 class="page-title">Comissões (preparadas)</h1>

    @can('create', App\Domains\CRM\Models\CommissionRule::class)
        <div class="card" style="margin-bottom:1rem;">
            <h2 style="margin-top:0;">Nova regra</h2>
            <form method="POST" action="{{ route('crm.commissions.rules.store') }}">
                @csrf
                <div class="form-grid">
                    <div>
                        <label>Nome</label>
                        <input type="text" name="name" required>
                    </div>
                    <div>
                        <label>Percentual</label>
                        <input type="number" step="0.01" min="0" max="100" name="percent" value="5" required>
                    </div>
                    <div>
                        <label>Valor mínimo</label>
                        <input type="number" step="0.01" min="0" name="min_amount" value="0">
                    </div>
                    <div>
                        <label>Ativa</label>
                        <select name="is_active">
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                </div>
                <div class="actions" style="margin-top:1rem;">
                    <button class="btn btn-primary" type="submit">Salvar regra</button>
                </div>
            </form>
        </div>
    @endcan

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Regras</h2>
        <table class="table">
            <thead><tr><th>Nome</th><th>%</th><th>Mínimo</th><th>Ativa</th></tr></thead>
            <tbody>
            @forelse($rules as $rule)
                <tr>
                    <td>{{ $rule->name }}</td>
                    <td>{{ number_format((float) $rule->percent, 2, ',', '.') }}%</td>
                    <td>R$ {{ number_format((float) ($rule->min_amount ?? 0), 2, ',', '.') }}</td>
                    <td>{{ $rule->is_active ? 'Sim' : 'Não' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Nenhuma regra.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $rules->links() }}</div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Lançamentos preparados</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Oportunidade</th>
                <th>Vendedor</th>
                <th>Base</th>
                <th>%</th>
                <th>Comissão</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->opportunity?->title }}</td>
                    <td>{{ $entry->user?->name }}</td>
                    <td>R$ {{ number_format((float) $entry->base_amount, 2, ',', '.') }}</td>
                    <td>{{ number_format((float) $entry->percent, 2, ',', '.') }}%</td>
                    <td>R$ {{ number_format((float) $entry->commission_amount, 2, ',', '.') }}</td>
                    <td>{{ $entry->status?->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhuma comissão preparada.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $entries->links() }}</div>
    </div>
@endsection
