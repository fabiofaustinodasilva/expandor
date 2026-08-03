@extends('layouts.guest')

@section('title', 'Planos')

@section('content')
    <style>
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
            padding: 0.25rem 0;
            color: var(--muted);
            font-size: 0.92rem;
            border-bottom: 1px solid rgba(42, 49, 66, 0.55);
        }
        .features li.on { color: var(--text); }
        .top-actions { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem; }
    </style>

    <h1 style="margin-top:0;">Planos Expandor</h1>
    <p class="muted">Mensal ou anual (10% de economia). Escolha o limite certo para sua operação de campo.</p>

    <div class="top-actions">
        <a class="btn" style="background:#F59E0B;color:#111;" href="{{ route('signup.create') }}">Teste grátis</a>
        <a class="btn btn-ghost" style="width:auto;" href="{{ route('login') }}">Já tenho conta</a>
    </div>

    <div class="grid">
        @forelse($plans as $plan)
            @php
                $map = $plan->featureMap();
                $yearly = $plan->yearlyPrice();
            @endphp
            <div class="card">
                @if($plan->is_featured)
                    <div class="badge-featured">Mais vendido</div>
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
                        Anual: R$ {{ number_format($yearly, 2, ',', '.') }}/ano
                    </div>
                @endif
                <p class="muted">{{ $plan->description }}</p>

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
                    <a class="btn btn-full" href="{{ route('marketplace.subscribe', ['plan_id' => $plan->id]) }}">Assinar</a>
                @else
                    <a class="btn btn-ghost" href="{{ route('signup.create') }}">Começar teste grátis</a>
                @endif
            </div>
        @empty
            <div class="card">Nenhum plano ativo no momento.</div>
        @endforelse
    </div>
@endsection
