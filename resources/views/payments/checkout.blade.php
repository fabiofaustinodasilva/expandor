@extends('layouts.guest')

@section('title', 'Checkout')

@section('content')
    <h1 style="margin-top:0;">Finalizar assinatura</h1>
    <p class="muted">Plano: <strong>{{ $plan->name }}</strong> — R$ {{ number_format((float) $plan->price, 2, ',', '.') }}/mês</p>

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

            <label for="billing_cycle">Ciclo</label>
            <select id="billing_cycle" name="billing_cycle">
                <option value="monthly" @selected(old('billing_cycle', 'monthly') === 'monthly')>Mensal</option>
                <option value="yearly" @selected(old('billing_cycle') === 'yearly')>Anual (10% off)</option>
            </select>

            <label for="payment_method">Forma de pagamento</label>
            <select id="payment_method" name="payment_method">
                <option value="PIX">PIX</option>
                <option value="BOLETO">Boleto</option>
                <option value="CREDIT_CARD">Cartão</option>
                <option value="UNDEFINED">Definir no gateway</option>
            </select>

            <button type="submit">Ir para pagamento</button>
        </form>
    </div>
@endsection
