@extends('layouts.guest')

@section('title', 'Checkout')

@section('content')
    <h1 style="margin-top:0;">Finalizar assinatura</h1>
    <p class="muted">
        Plano: <strong>{{ $plan->name }}</strong>
        — R$ {{ number_format((float) $plan->price, 2, ',', '.') }}/mês
        · Anual: R$ {{ number_format($plan->yearlyPrice(), 2, ',', '.') }}
    </p>

    <div class="card" style="max-width:520px;">
        <form method="POST" action="{{ route('checkout.store') }}">
            @csrf
            <input type="hidden" name="plan_id" value="{{ $plan->id }}">

            <label for="company_name">Nome da empresa</label>
            <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required>

            <label for="buyer_name">Seu nome</label>
            <input id="buyer_name" name="buyer_name" type="text" value="{{ old('buyer_name') }}" required>

            <label for="buyer_email">E-mail de acesso</label>
            <input id="buyer_email" name="buyer_email" type="email" value="{{ old('buyer_email') }}" required>

            <label for="buyer_document">CPF/CNPJ</label>
            <input id="buyer_document" name="buyer_document" type="text" value="{{ old('buyer_document') }}">

            <label for="buyer_phone">Telefone</label>
            <input id="buyer_phone" name="buyer_phone" type="text" value="{{ old('buyer_phone') }}">

            <label for="billing_cycle">Ciclo de cobrança</label>
            <select id="billing_cycle" name="billing_cycle">
                @foreach($billingCycles as $value => $label)
                    <option value="{{ $value }}" @selected($defaultBillingCycle === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="muted" id="yearly-hint" style="margin-top:-0.5rem; margin-bottom:1rem; font-size:0.85rem;">
                No anual: R$ {{ number_format($plan->yearlyPrice(), 2, ',', '.') }} cobrados uma vez por ano.
            </p>

            <label for="payment_method">Forma de pagamento</label>
            <select id="payment_method" name="payment_method">
                @foreach($paymentMethods as $value => $label)
                    <option value="{{ $value }}" @selected($defaultPaymentMethod === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <label for="admin_password">Senha do administrador (opcional)</label>
            <input id="admin_password" name="admin_password" type="password" value="" autocomplete="new-password" placeholder="Gerada automaticamente se vazia">

            <label for="admin_password_confirmation">Confirmar senha</label>
            <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" value="" autocomplete="new-password">

            <button type="submit">Ir para pagamento</button>
        </form>
    </div>
@endsection
