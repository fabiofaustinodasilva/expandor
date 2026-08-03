@extends('layouts.guest')

@section('title', 'Planos')

@section('content')
    <h1 style="margin-top:0;">Escolha seu plano</h1>
    <p class="muted">Crie sua conta Expandor em minutos. Pagamento seguro e provisionamento automático.</p>

    <div class="grid">
        @forelse($plans as $plan)
            <div class="card">
                <h2 style="margin-top:0;">{{ $plan->name }}</h2>
                <div style="font-size:1.6rem; font-weight:700; margin-bottom:0.5rem;">
                    R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                    <span style="font-size:0.9rem; color:var(--muted);">/mês</span>
                </div>
                <p class="muted" style="min-height:3rem;">{{ $plan->description }}</p>
                <a class="btn" href="{{ route('checkout.create', ['plan_id' => $plan->id]) }}">Assinar</a>
            </div>
        @empty
            <div class="card">Nenhum plano pago disponível no momento.</div>
        @endforelse
    </div>
@endsection
