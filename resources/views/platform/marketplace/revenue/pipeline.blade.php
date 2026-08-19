@extends('layouts.platform')

@section('title', 'Central Comercial')

@section('content')
    @php
        $avg = $metrics['avg_first_contact_minutes'] ?? null;
        $avgLabel = $avg === null ? '—' : ( $avg < 60 ? $avg.' min' : round($avg / 60, 1).' h' );
    @endphp
    <style>
        .funnel-metrics { display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:.75rem; margin-bottom:1rem; }
        .funnel-metric { padding:.85rem 1rem; }
        .funnel-metric strong { display:block; font-size:1.35rem; margin-top:.2rem; }
        .funnel-board { display:flex; gap:.75rem; overflow-x:auto; padding-bottom:.5rem; align-items:flex-start; }
        .funnel-col { min-width:260px; width:260px; background:var(--bg-soft, #1E2330); border:1px solid var(--border, #2A3142); border-radius:12px; padding:.75rem; }
        .funnel-col h2 { margin:0 0 .75rem; font-size:.92rem; display:flex; justify-content:space-between; gap:.5rem; color:var(--text, #F3F5F9); }
        .funnel-card { background:var(--bg-elevated, #171A22); border:1px solid var(--border, #2A3142); border-radius:12px; padding:.85rem; margin-bottom:.75rem; display:flex; flex-direction:column; gap:.35rem; color:var(--text, #F3F5F9); }
        .funnel-card strong { color:var(--text, #F3F5F9); }
        .funnel-card .meta { color:var(--muted, #9AA3B5); font-size:.82rem; }
        .funnel-card .meta a { color:var(--accent, #F59E0B); text-decoration:underline; }
        .funnel-actions { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.5rem; }
        .funnel-actions .btn { padding:.35rem .6rem; font-size:.8rem; }
        .funnel-stage { width:100%; margin-top:.4rem; background:var(--bg-soft, #1E2330); color:var(--text, #F3F5F9); border-color:var(--border, #2A3142); }
        .funnel-wa { background:#25D366; border-color:#25D366; color:#fff; }
        .funnel-schedule-form { display:none; margin-top:.5rem; }
        .funnel-schedule-form.is-open { display:block; }
        .funnel-schedule-form label { color:var(--muted, #9AA3B5); }
        @media (max-width: 800px) {
            .funnel-board { flex-direction:column; overflow:visible; }
            .funnel-col { width:100%; min-width:0; }
        }
    </style>

    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Central Comercial</h1>
        <div class="header-meta">Funil comercial dos pedidos de demonstração do site.</div>
    </div>

    <div class="funnel-metrics">
        <div class="card funnel-metric"><div class="header-meta">Novos leads</div><strong>{{ $metrics['new'] }}</strong></div>
        <div class="card funnel-metric"><div class="header-meta">Aguardando contato</div><strong>{{ $metrics['awaiting_contact'] }}</strong></div>
        <div class="card funnel-metric"><div class="header-meta">Demos agendadas</div><strong>{{ $metrics['demo_scheduled'] }}</strong></div>
        <div class="card funnel-metric"><div class="header-meta">Demos realizadas</div><strong>{{ $metrics['demo_completed'] }}</strong></div>
        <div class="card funnel-metric"><div class="header-meta">Propostas</div><strong>{{ $metrics['proposal'] }}</strong></div>
        <div class="card funnel-metric"><div class="header-meta">Fechados</div><strong>{{ $metrics['closed'] }}</strong></div>
        <div class="card funnel-metric"><div class="header-meta">1º contato</div><strong>{{ $avgLabel }}</strong></div>
        <div class="card funnel-metric"><div class="header-meta">Lead → Demo</div><strong>{{ number_format($metrics['lead_to_demo'], 1, ',', '.') }}%</strong></div>
        <div class="card funnel-metric"><div class="header-meta">Demo → Cliente</div><strong>{{ number_format($metrics['demo_to_customer'], 1, ',', '.') }}%</strong></div>
    </div>

    <div style="margin-bottom:1rem;">
        @if($includeLost)
            <a class="btn btn-ghost" href="{{ route('platform.marketplace.pipeline.index') }}">Ocultar perdidos</a>
        @else
            <a class="btn btn-ghost" href="{{ route('platform.marketplace.pipeline.index', ['lost' => 1]) }}">Ver perdidos</a>
        @endif
    </div>

    <div class="funnel-board">
        @foreach($columns as $key => $column)
            <section class="funnel-col">
                <h2>{{ $column['label'] }} <span class="header-meta">{{ $column['items']->count() }}</span></h2>
                @forelse($column['items'] as $item)
                    @include('platform.marketplace.revenue.partials.lead-card', ['item' => $item, 'stages' => $stages])
                @empty
                    <div class="header-meta">Nenhum lead.</div>
                @endforelse
            </section>
        @endforeach
    </div>

    @if($includeLost && $lostItems->isNotEmpty())
        <div class="card" style="margin-top:1rem;">
            <h2 style="margin-top:0;">Perdidos</h2>
            @foreach($lostItems as $item)
                @include('platform.marketplace.revenue.partials.lead-card', ['item' => $item, 'stages' => $stages])
            @endforeach
        </div>
    @endif
@endsection
