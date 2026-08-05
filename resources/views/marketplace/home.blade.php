@extends('layouts.guest')

@section('title', 'Expandor — Vendas porta a porta')

@section('content')
    <style>
        .hero { padding: 2.5rem 0 2rem; border-bottom: 1px solid var(--border); margin-bottom: 2rem; }
        .hero-brand { font-size: clamp(2.4rem, 6vw, 3.6rem); font-weight: 800; letter-spacing: -0.03em; margin: 0 0 0.75rem; line-height: 1.05; }
        .hero-brand span { color: #F59E0B; }
        .hero p { max-width: 36rem; font-size: 1.1rem; color: var(--muted); margin: 0 0 1.5rem; }
        .cta-row { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .cta-row .btn { width: auto; min-width: 10rem; }
        .section-title { margin: 0 0 0.35rem; font-size: 1.35rem; }
        .badge-featured {
            display: inline-block; margin-bottom: 0.65rem; padding: 0.2rem 0.6rem; border-radius: 999px;
            background: rgba(245, 158, 11, 0.18); color: #FBBF24; font-size: 0.75rem; font-weight: 700;
        }
        .price { font-size: 1.7rem; font-weight: 700; margin-bottom: 0.35rem; }
        .price small { font-size: 0.85rem; color: var(--muted); font-weight: 500; }
    </style>

    <section class="hero">
        <h1 class="hero-brand">Expand<span>or</span></h1>
        <p>Sistema de vendas porta a porta — organize vendedores, visitas, clientes e campanhas em um só lugar.</p>
        <div class="cta-row">
            <a class="btn btn-primary" href="{{ route('marketplace.plans') }}">Ver planos</a>
            <a class="btn btn-ghost" href="{{ route('signup.create') }}">Começar agora</a>
            <a class="btn btn-ghost" href="{{ route('login') }}">Entrar</a>
        </div>
    </section>

    <section>
        <h2 class="section-title">Planos em destaque</h2>
        <p class="muted" style="margin-top:0; margin-bottom:1.25rem;">Assine online com PIX ou cartão. Sua empresa fica pronta após o pagamento.</p>

        <div class="grid">
            @forelse($featuredPlans as $plan)
                <div class="card">
                    @if($plan->is_featured)
                        <div class="badge-featured">Mais escolhido</div>
                    @endif
                    <h3 style="margin:0 0 0.5rem;">{{ $plan->name }}</h3>
                    <div class="price">
                        @if((float) $plan->price <= 0)
                            Grátis
                        @else
                            R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                            <small>/mês</small>
                        @endif
                    </div>
                    <p class="muted" style="min-height:2.5rem;">{{ $plan->description }}</p>
                    @if((float) $plan->price > 0)
                        <a class="btn btn-primary" href="{{ route('marketplace.subscribe', ['plan_id' => $plan->id]) }}">Assinar</a>
                    @else
                        <a class="btn btn-ghost" href="{{ route('signup.create') }}">Começar agora</a>
                    @endif
                </div>
            @empty
                <div class="card">Nenhum plano disponível no momento.</div>
            @endforelse
        </div>
    </section>
@endsection
