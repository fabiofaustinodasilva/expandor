@extends('layouts.guest')

@section('title', 'Assinar')

@section('content')
    <style>
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
        .btn { width: 100%; }
    </style>

    <h1 style="margin-top:0;">Escolha o plano para assinar</h1>
    <p class="muted">Você será direcionado ao checkout seguro com PIX ou cartão.</p>

    <div class="grid">
        @forelse($plans as $plan)
            <div class="card">
                @if($plan->is_featured)
                    <div class="badge-featured">Mais vendido</div>
                @endif
                <h2 style="margin-top:0;">{{ $plan->name }}</h2>
                <div style="font-size:1.5rem; font-weight:700; margin-bottom:0.35rem;">
                    R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                    <span class="muted" style="font-size:0.9rem;">/mês</span>
                </div>
                <div class="muted" style="margin-bottom:1rem;">
                    Anual: R$ {{ number_format($plan->yearlyPrice(), 2, ',', '.') }}
                </div>
                <a class="btn" href="{{ route('checkout.create', ['plan_id' => $plan->id]) }}">Continuar</a>
            </div>
        @empty
            <div class="card">Nenhum plano pago disponível.</div>
        @endforelse
    </div>
@endsection
