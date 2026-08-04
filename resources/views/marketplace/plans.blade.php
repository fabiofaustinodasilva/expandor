@extends('layouts.guest')

@section('title', 'Planos')

@section('content')
    <style>
        .plans-wrap { max-width: 1100px; margin: 0 auto; }
        .btn { width: auto; }
        .btn-ghost {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            width: 100%;
        }
        .btn-full { width: 100%; }
        .badge-featured {
            display: inline-block;
            margin-bottom: 0.65rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.18);
            color: #FBBF24;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .price { font-size: 1.7rem; font-weight: 700; }
        .price small { font-size: 0.85rem; color: var(--muted); font-weight: 500; }
        .limits, .features { list-style: none; padding: 0; margin: 0.85rem 0 1rem; }
        .limits li, .features li {
            padding: 0.35rem 0;
            color: var(--muted);
            font-size: 0.92rem;
            border-bottom: 1px solid rgba(42, 49, 66, 0.55);
        }
        .features li.on { color: var(--text); }
        .top-actions { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem; }
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.1rem;
            align-items: stretch;
        }
        .plan-card {
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .plan-card:hover { transform: translateY(-4px); }
        .plan-card.is-recommended {
            border: 1px solid rgba(245, 158, 11, 0.55);
            box-shadow: 0 16px 40px rgba(245, 158, 11, 0.12);
        }
        .plan-highlight {
            margin: 0.5rem 0 0.85rem;
            padding: 0.65rem 0.75rem;
            border-radius: 0.65rem;
            background: rgba(59, 130, 246, 0.12);
            color: var(--text);
            font-size: 0.88rem;
        }
        .compare-note { margin-top: 1.5rem; color: var(--muted); font-size: 0.9rem; }
        @media (max-width: 640px) {
            .plans-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="plans-wrap">
        <h1 style="margin-top:0;">Compare os planos Expandor</h1>
        <p class="muted">Escolha o ritmo certo para sua operação de campo. Destaque no plano recomendado e comece pelo teste grátis.</p>

        <div class="top-actions">
            <a class="btn" style="background:#F59E0B;color:#111;" href="{{ route('signup.create') }}">Começar teste grátis</a>
            <a class="btn btn-ghost" style="width:auto;" href="{{ route('marketplace.home') }}#demo">Solicitar demonstração</a>
            <a class="btn btn-ghost" style="width:auto;" href="{{ route('login') }}">Já tenho conta</a>
        </div>

        <div class="plans-grid">
            @forelse($plans as $plan)
                @php
                    $map = $plan->featureMap();
                    $yearly = $plan->yearlyPrice();
                    $enabled = collect($map)->filter()->keys()->map(fn ($k) => $featureLabels[$k] ?? $k)->values();
                @endphp
                <div class="card plan-card {{ $plan->is_featured ? 'is-recommended' : '' }}">
                    @if($plan->is_featured)
                        <div class="badge-featured">Recomendado</div>
                    @endif
                    <h2 style="margin:0 0 0.5rem; font-size:1.25rem;">{{ $plan->name }}</h2>
                    <div class="price">
                        @if((float) $plan->price <= 0)
                            Grátis
                        @else
                            R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                            <small>/mês</small>
                        @endif
                    </div>
                    @if((float) $plan->price > 0)
                        <div class="muted" style="margin:0.35rem 0 0.75rem;">
                            Anual: R$ {{ number_format($yearly, 2, ',', '.') }}/ano (10% off)
                        </div>
                    @endif
                    <p class="muted">{{ $plan->description }}</p>

                    @if($enabled->isNotEmpty())
                        <div class="plan-highlight">
                            Benefícios em destaque: {{ $enabled->take(3)->implode(' · ') }}
                        </div>
                    @endif

                    <ul class="limits">
                        <li>Usuários: {{ $plan->max_users ?? 'Ilimitado' }}</li>
                        <li>Pontos/clientes: {{ $plan->max_properties ?? 'Ilimitado' }}</li>
                        <li>Campanhas: {{ $plan->max_campaigns ?? 'Ilimitado' }}</li>
                        <li>Visitas: {{ $plan->max_visits ?? 'Ilimitado' }}</li>
                        <li>Storage: {{ $plan->max_storage_mb ? number_format($plan->max_storage_mb, 0, ',', '.').' MB' : 'Ilimitado' }}</li>
                    </ul>

                    <ul class="features">
                        @foreach($featureLabels as $key => $label)
                            <li class="{{ ($map[$key] ?? false) ? 'on' : '' }}">
                                {{ ($map[$key] ?? false) ? '✓' : '—' }} {{ $label }}
                            </li>
                        @endforeach
                    </ul>

                    @if((float) $plan->price > 0)
                        <a class="btn btn-full" href="{{ route('marketplace.subscribe', ['plan_id' => $plan->id]) }}">Assinar {{ $plan->name }}</a>
                    @else
                        <a class="btn btn-ghost" href="{{ route('signup.create') }}">Começar teste grátis</a>
                    @endif
                </div>
            @empty
                <div class="card">Nenhum plano ativo no momento.</div>
            @endforelse
        </div>

        <p class="compare-note">Comparação visual lado a lado dos recursos inclusos em cada plano. Precisa de ajuda para escolher? Solicite uma demonstração.</p>
    </div>
@endsection
