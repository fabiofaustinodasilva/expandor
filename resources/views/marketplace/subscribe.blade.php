@extends('layouts.guest')

@section('title', 'Assinar Expandor')

@section('content')
<style>
    .sub-wrap { max-width: 980px; margin: 0 auto; }
    .sub-wrap h1 { margin: 0 0 0.35rem; font-size: clamp(1.45rem, 3vw, 1.85rem); letter-spacing: -0.02em; }
    .sub-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }
    .sub-card {
        display: flex; flex-direction: column; min-height: 100%;
        transition: transform 0.18s ease, border-color 0.18s ease;
    }
    .sub-card:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--accent) 40%, var(--border)); }
    .sub-card h2 { margin: 0 0 0.5rem; font-size: 1.2rem; }
    .sub-price { font-size: 1.65rem; font-weight: 800; margin-bottom: 0.25rem; letter-spacing: -0.02em; }
    .sub-price span { font-size: 0.9rem; color: var(--muted); font-weight: 550; }
    .sub-card .btn { margin-top: auto; width: 100%; min-height: 2.75rem; }
    .badge-featured {
        display: inline-block; margin-bottom: 0.65rem; padding: 0.2rem 0.6rem;
        border-radius: 999px; background: rgba(245, 158, 11, 0.18); color: #FBBF24;
        font-size: 0.75rem; font-weight: 700;
    }
</style>

<div class="sub-wrap">
    <div class="header-meta"><a href="{{ route('marketplace.plans') }}" style="color:inherit;text-decoration:none;">← Ver planos</a></div>
    <h1>Escolha o plano da sua equipe</h1>
    <p class="muted" style="margin:0 0 0.25rem;">Sistema de vendas porta a porta — pagamento seguro com PIX ou cartão.</p>

    <div class="sub-grid">
        @forelse($plans as $plan)
            <div class="card sub-card">
                @if($plan->is_featured)
                    <div class="badge-featured">Mais escolhido</div>
                @endif
                <h2>{{ $plan->name }}</h2>
                <div class="sub-price">
                    R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                    <span>/mês</span>
                </div>
                <div class="muted" style="margin-bottom:1.25rem; font-size:0.9rem;">
                    Anual: R$ {{ number_format($plan->yearlyPrice(), 2, ',', '.') }}
                </div>
                <a class="btn btn-primary" href="{{ route('checkout.create', ['plan_id' => $plan->id]) }}">Continuar</a>
            </div>
        @empty
            <div class="card">Nenhum plano pago disponível no momento.</div>
        @endforelse
    </div>
</div>
@endsection
