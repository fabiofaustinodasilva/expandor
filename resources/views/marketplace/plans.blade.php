@extends('layouts.guest')

@section('title', 'Planos')

@section('content')
    @php
        $catalog = $premiumData['commercial_plans'] ?? [];
        $planCards = $catalog['plans'] ?? [];
        $enterprise = $catalog['enterprise'] ?? null;
        $planFeatures = $catalog['features'] ?? [];
        $demoHref = route('marketplace.home').'#demo';
    @endphp
    <style>
        .plans-wrap { max-width: 1100px; margin: 0 auto; }
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.1rem;
            align-items: stretch;
        }
        .plan-card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .plan-card.is-recommended {
            border: 1px solid rgba(245, 158, 11, 0.55);
            box-shadow: 0 16px 40px rgba(245, 158, 11, 0.12);
        }
        .badge-featured {
            display: inline-block;
            margin-bottom: 0.65rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.18);
            color: #FBBF24;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .price { font-size: 1.7rem; font-weight: 700; }
        .price small { font-size: 0.85rem; color: var(--muted); font-weight: 500; }
        .features { list-style: none; padding: 0; margin: 0.85rem 0 1rem; }
        .features li {
            padding: 0.35rem 0;
            color: var(--text);
            font-size: 0.92rem;
            border-bottom: 1px solid rgba(42, 49, 66, 0.55);
        }
        .top-actions { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem; }
        .plan-card .btn { margin-top: auto; width: 100%; text-align: center; }
        .plan-enterprise {
            margin-top: 1.25rem;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .compare-note { margin-top: 1.5rem; color: var(--muted); font-size: 0.9rem; }
        @media (max-width: 768px) {
            .plans-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="plans-wrap">
        <h1 style="margin-top:0;">Todo o poder do Expandor. Escolha pelo tamanho da sua equipe.</h1>
        <p class="muted">Os três planos principais incluem o mesmo conjunto de recursos. A diferença é o tamanho da operação.</p>

        <div class="top-actions">
            <a class="btn" style="background:#F59E0B;color:#111;" href="{{ $demoHref }}">Agendar demonstração</a>
            <a class="btn btn-ghost" style="width:auto;" href="{{ route('marketplace.home') }}">Voltar ao início</a>
            <a class="btn btn-ghost" style="width:auto;" href="{{ route('login') }}">Já tenho conta</a>
        </div>

        <div class="plans-grid">
            @foreach($planCards as $planCard)
                <article class="card plan-card {{ !empty($planCard['featured']) ? 'is-recommended' : '' }}" data-plan="{{ $planCard['key'] ?? '' }}">
                    @if(!empty($planCard['badge']))
                        <div class="badge-featured">{{ $planCard['badge'] }}</div>
                    @endif
                    <h2 style="margin:0 0 0.5rem; font-size:1.25rem;">{{ $planCard['name'] ?? '' }}</h2>
                    <div class="price">
                        {{ $planCard['price_label'] ?? '' }}
                        @if(!empty($planCard['period']))
                            <small>{{ $planCard['period'] }}</small>
                        @endif
                    </div>
                    <p class="muted">{{ $planCard['audience'] ?? '' }}</p>
                    <ul class="features">
                        @foreach($planFeatures as $featureLabel)
                            <li>✓ {{ $featureLabel }}</li>
                        @endforeach
                    </ul>
                    <a class="btn" href="{{ $demoHref }}">{{ $planCard['cta_label'] ?? 'Agendar demonstração' }}</a>
                </article>
            @endforeach
        </div>

        @if(!empty($enterprise))
            <article class="card plan-card plan-enterprise" data-plan="enterprise">
                <div>
                    <h2 style="margin:0 0 0.35rem; font-size:1.25rem;">{{ $enterprise['name'] ?? 'Enterprise' }}</h2>
                    <div class="price">{{ $enterprise['price_label'] ?? 'Sob consulta' }}</div>
                    <p class="muted" style="margin-bottom:0;">{{ $enterprise['audience'] ?? '' }}</p>
                </div>
                <a class="btn btn-ghost" href="{{ $demoHref }}">{{ $enterprise['cta_label'] ?? 'Agendar demonstração' }}</a>
            </article>
        @endif

        @if(!empty($catalog['note']))
            <p class="compare-note">{{ $catalog['note'] }}</p>
        @endif
    </div>
@endsection
