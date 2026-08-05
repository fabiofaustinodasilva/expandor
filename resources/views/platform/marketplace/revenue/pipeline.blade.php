@extends('layouts.platform')

@section('title', 'Funil comercial')

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Funil comercial</h1>
        <div class="header-meta">Gestão de estágios dos Leads do site.</div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Lead</th>
                <th>Empresa</th>
                <th>Score</th>
                <th>Estágio</th>
                <th>Notas</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->lead?->name }}</strong>
                        <div class="header-meta">{{ $item->lead?->email }}</div>
                    </td>
                    <td>{{ $item->lead?->company_name ?: '—' }}</td>
                    <td>
                        @if($item->lead?->score)
                            {{ $item->lead->score->score }}
                            <span class="badge">{{ $item->lead->score->temperature?->label() }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td colspan="3">
                        <form method="POST" action="{{ route('platform.marketplace.pipeline.update', $item) }}" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                            @csrf
                            @method('PUT')
                            <select name="stage" class="form-control" style="width:auto; min-width:10rem;">
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->value }}" @selected($item->stage === $stage)>{{ $stage->label() }}</option>
                                @endforeach
                            </select>
                            <input class="form-control" name="notes" value="{{ old('notes', $item->notes) }}" placeholder="Notas" style="min-width:12rem; flex:1;">
                            <button class="btn btn-primary" type="submit">Salvar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhum lead no pipeline ainda.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
