@extends('layouts.guest')

@section('title', 'Finalizar assinatura')

@section('content')
@php
    $monthly = (float) $plan->price;
    $yearly = $plan->yearlyPrice();
@endphp
<style>
    .ck-wrap { max-width: 980px; margin: 0 auto; }
    .ck-header { margin-bottom: 1.5rem; }
    .ck-header h1 { margin: 0 0 0.35rem; font-size: clamp(1.45rem, 3vw, 1.85rem); letter-spacing: -0.02em; }
    .ck-steps {
        display: flex; gap: 0.5rem; flex-wrap: wrap; margin: 0 0 1.5rem; padding: 0; list-style: none;
    }
    .ck-steps li {
        flex: 1; min-width: 120px; padding: 0.65rem 0.75rem; border-radius: 0.75rem;
        border: 1px solid var(--border); background: var(--bg-elevated); color: var(--muted);
        font-size: 0.8rem; font-weight: 650; text-align: center;
    }
    .ck-steps li.is-active { color: var(--text); border-color: color-mix(in srgb, var(--accent) 45%, var(--border)); box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 25%, transparent); }
    .ck-grid {
        display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr); gap: 1.25rem; align-items: start;
    }
    .ck-panel { padding: 1.35rem; }
    .ck-panel h2 {
        margin: 0 0 1rem; font-size: 1rem; letter-spacing: 0.04em; text-transform: uppercase; color: var(--muted); font-weight: 700;
    }
    .ck-block { margin-bottom: 1.5rem; padding-bottom: 1.35rem; border-bottom: 1px solid var(--border); }
    .ck-block:last-of-type { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
    .ck-block h3 { margin: 0 0 0.85rem; font-size: 1.05rem; }
    .ck-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
    .ck-summary {
        position: sticky; top: 1rem; padding: 1.35rem;
        background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 8%, var(--bg-elevated)), var(--bg-elevated));
    }
    .ck-summary .plan-name { font-size: 1.25rem; font-weight: 750; margin: 0.25rem 0 0.75rem; }
    .ck-price { font-size: 1.85rem; font-weight: 800; letter-spacing: -0.02em; }
    .ck-price span { font-size: 0.9rem; color: var(--muted); font-weight: 550; }
    .ck-line { display: flex; justify-content: space-between; gap: 1rem; padding: 0.55rem 0; border-bottom: 1px solid var(--border); font-size: 0.92rem; }
    .ck-line:last-child { border-bottom: 0; }
    .ck-secure { margin-top: 1rem; color: var(--muted); font-size: 0.82rem; line-height: 1.4; }
    .ck-submit { margin-top: 1.25rem; width: 100%; min-height: 3rem; font-size: 1rem; }
    @media (max-width: 820px) {
        .ck-grid { grid-template-columns: 1fr; }
        .ck-summary { position: static; order: -1; }
        .ck-row { grid-template-columns: 1fr; }
        .ck-steps li { min-width: calc(50% - 0.35rem); flex: 1 1 calc(50% - 0.35rem); }
    }
</style>

<div class="ck-wrap">
    <div class="ck-header">
        <div class="header-meta"><a href="{{ route('marketplace.plans') }}" style="color:inherit;text-decoration:none;">← Voltar aos planos</a></div>
        <h1>Finalizar assinatura</h1>
        <p class="muted" style="margin:0;">Organize sua equipe de vendas porta a porta com o Expandor.</p>
    </div>

    <ol class="ck-steps" aria-label="Etapas da assinatura">
        <li class="is-active">1. Plano</li>
        <li class="is-active">2. Empresa</li>
        <li class="is-active">3. Responsável</li>
        <li class="is-active">4. Pagamento</li>
    </ol>

    <div class="ck-grid">
        <div class="card ck-panel">
            <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                <div class="ck-block">
                    <h3>Dados da empresa</h3>
                    <label for="company_name">Nome da empresa</label>
                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required autocomplete="organization" placeholder="Ex.: Campo Norte Telecom">
                </div>

                <div class="ck-block">
                    <h3>Dados do responsável</h3>
                    <div class="ck-row">
                        <div>
                            <label for="buyer_name">Seu nome</label>
                            <input id="buyer_name" name="buyer_name" type="text" value="{{ old('buyer_name') }}" required autocomplete="name" placeholder="Nome completo">
                        </div>
                        <div>
                            <label for="buyer_email">E-mail de acesso</label>
                            <input id="buyer_email" name="buyer_email" type="email" value="{{ old('buyer_email') }}" required autocomplete="email" placeholder="voce@empresa.com">
                        </div>
                    </div>
                    <div class="ck-row">
                        <div>
                            <label for="buyer_document">CPF ou CNPJ</label>
                            <input id="buyer_document" name="buyer_document" type="text" value="{{ old('buyer_document') }}" required placeholder="000.000.000-00 ou 00.000.000/0000-00">
                        </div>
                        <div>
                            <label for="buyer_phone">Telefone / WhatsApp</label>
                            <input id="buyer_phone" name="buyer_phone" type="text" value="{{ old('buyer_phone') }}" required autocomplete="tel" placeholder="(11) 99999-9999">
                        </div>
                    </div>
                </div>

                <div class="ck-block">
                    <h3>Pagamento</h3>
                    <div class="ck-row">
                        <div>
                            <label for="billing_cycle">Ciclo de cobrança</label>
                            <select id="billing_cycle" name="billing_cycle">
                                @foreach($billingCycles as $value => $label)
                                    <option value="{{ $value }}" @selected($defaultBillingCycle === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="payment_method">Forma de pagamento</label>
                            <select id="payment_method" name="payment_method">
                                @foreach($paymentMethods as $value => $label)
                                    <option value="{{ $value }}" @selected($defaultPaymentMethod === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="muted" id="yearly-hint" style="margin-top:0.35rem; margin-bottom:0; font-size:0.85rem;">
                        No anual: R$ {{ number_format($yearly, 2, ',', '.') }} cobrados uma vez por ano.
                    </p>
                </div>

                <div class="ck-block">
                    <h3>Acesso do administrador</h3>
                    <div class="ck-row">
                        <div>
                            <label for="admin_password">Senha</label>
                            <input id="admin_password" name="admin_password" type="password" value="" required autocomplete="new-password" placeholder="Mínimo conforme política de segurança">
                        </div>
                        <div>
                            <label for="admin_password_confirmation">Confirmar senha</label>
                            <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" value="" required autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary ck-submit" type="submit">Confirmar e ir para pagamento</button>
                <p class="ck-secure">Você será redirecionado ao checkout seguro do Mercado Pago (PIX ou cartão). Após a aprovação, o acesso é liberado automaticamente.</p>
            </form>
        </div>

        <aside class="card ck-summary" aria-label="Resumo da assinatura">
            <div class="header-meta">Resumo do plano</div>
            <div class="plan-name">{{ $plan->name }}</div>
            <div class="ck-price" id="ck-price-display">
                R$ {{ number_format($monthly, 2, ',', '.') }} <span>/mês</span>
            </div>
            <div style="margin:1rem 0 0.35rem;">
                <div class="ck-line"><span>Mensal</span><strong>R$ {{ number_format($monthly, 2, ',', '.') }}</strong></div>
                <div class="ck-line"><span>Anual</span><strong>R$ {{ number_format($yearly, 2, ',', '.') }}</strong></div>
            </div>
            <div class="ck-secure">
                Ideal para equipes de vendas externas: mapa, visitas, clientes, campanhas e resultados em um só lugar.
            </div>
        </aside>
    </div>
</div>
@endsection
