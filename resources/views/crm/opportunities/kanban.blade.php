@extends('layouts.app')

@section('title', 'Kanban de vendas')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
        <h1 class="page-title" style="margin:0;">Kanban de vendas</h1>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('crm.opportunities.index') }}">Lista</a>
            @can('create', App\Domains\CRM\Models\Opportunity::class)
                <a class="btn btn-primary" href="{{ route('crm.opportunities.create') }}">Nova oportunidade</a>
            @endcan
        </div>
    </div>

    <div style="display:flex; gap:1rem; overflow-x:auto; padding-bottom:1rem;">
        @foreach($stages as $stage)
            <div class="card" style="min-width:260px; flex:1; border-top:3px solid {{ $stage->color ?: 'var(--accent)' }};">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.75rem;">
                    <strong>{{ $stage->name }}</strong>
                    <span class="badge">{{ ($columns[$stage->id] ?? collect())->count() }}</span>
                </div>
                @foreach(($columns[$stage->id] ?? collect()) as $opportunity)
                    <div style="border:1px solid var(--border); border-radius:8px; padding:0.75rem; margin-bottom:0.65rem; background:var(--bg-soft);">
                        <div><strong>{{ $opportunity->title }}</strong></div>
                        <div class="header-meta">{{ $opportunity->owner?->name ?: 'Sem dono' }}</div>
                        <div style="margin:0.35rem 0;">R$ {{ number_format((float) $opportunity->amount, 2, ',', '.') }}</div>
                        @can('update', $opportunity)
                            <div class="actions" style="flex-wrap:wrap;">
                                <a class="btn btn-ghost" href="{{ route('crm.opportunities.edit', $opportunity) }}">Editar</a>
                                @unless($stage->is_won)
                                    <form method="POST" action="{{ route('crm.opportunities.win', $opportunity) }}">@csrf<button class="btn btn-primary" type="submit">Ganhar</button></form>
                                @endunless
                                @unless($stage->is_lost)
                                    <form method="POST" action="{{ route('crm.opportunities.lose', $opportunity) }}">@csrf<button class="btn btn-danger" type="submit">Perder</button></form>
                                @endunless
                                <form method="POST" action="{{ route('crm.opportunities.move', $opportunity) }}" style="display:flex; gap:0.35rem; width:100%;">
                                    @csrf
                                    <select name="pipeline_stage_id" style="flex:1;">
                                        @foreach($stages as $option)
                                            <option value="{{ $option->id }}" @selected($option->id === $stage->id)>{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-ghost" type="submit">Mover</button>
                                </form>
                            </div>
                        @endcan
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endsection
