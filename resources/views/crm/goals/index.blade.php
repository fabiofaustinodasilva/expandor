@extends('layouts.app')

@section('title', 'Metas de vendedores')

@section('content')
    <h1 class="page-title">Metas de vendedores</h1>

    @can('create', App\Domains\CRM\Models\SalesGoal::class)
        <div class="card" style="margin-bottom:1rem;">
            <h2 style="margin-top:0;">Nova / atualizar meta</h2>
            <form method="POST" action="{{ route('crm.goals.store') }}">
                @csrf
                <div class="form-grid">
                    <div>
                        <label>Vendedor</label>
                        <select name="user_id" required>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Período</label>
                        <select name="period_type">
                            @foreach($periods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Início</label>
                        <input type="date" name="period_start" value="{{ now()->startOfMonth()->toDateString() }}" required>
                    </div>
                    <div>
                        <label>Fim</label>
                        <input type="date" name="period_end" value="{{ now()->endOfMonth()->toDateString() }}" required>
                    </div>
                    <div>
                        <label>Meta valor</label>
                        <input type="number" step="0.01" min="0" name="target_amount" value="10000">
                    </div>
                    <div>
                        <label>Meta quantidade</label>
                        <input type="number" min="0" name="target_count" value="10">
                    </div>
                </div>
                <div class="actions" style="margin-top:1rem;">
                    <button class="btn btn-primary" type="submit">Salvar meta</button>
                </div>
            </form>
        </div>
    @endcan

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Vendedor</th>
                <th>Período</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Valor</th>
                <th>Qtd</th>
            </tr>
            </thead>
            <tbody>
            @forelse($goals as $goal)
                <tr>
                    <td>{{ $goal->user?->name }}</td>
                    <td>{{ $goal->period_type?->label() }}</td>
                    <td>{{ $goal->period_start?->format('d/m/Y') }}</td>
                    <td>{{ $goal->period_end?->format('d/m/Y') }}</td>
                    <td>R$ {{ number_format((float) $goal->target_amount, 2, ',', '.') }}</td>
                    <td>{{ $goal->target_count }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhuma meta cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $goals->links() }}</div>
    </div>
@endsection
