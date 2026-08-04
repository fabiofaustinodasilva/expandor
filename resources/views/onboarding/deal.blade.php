@extends('layouts.app')

@section('title', 'Negócio — Onboarding')

@section('content')
    <x-saas-onboarding-shell :progress="$progress" step-key="deal">
        <div style="margin-bottom:1rem;">
            <h1 class="page-title" style="margin:0;">Primeiro negócio</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Crie uma oportunidade inicial para ativar o pipeline.</p>
        </div>

        <div class="saas-onb-card" data-saas-deal="1">
            <form method="POST" action="{{ route('onboarding.deal.store') }}">
                @csrf
                <div class="grid grid-2" style="gap:1rem;">
                    @if(!empty($leads))
                        <div style="grid-column:1 / -1;">
                            <label for="lead_id">Cliente existente (opcional)</label>
                            <select id="lead_id" name="lead_id">
                                <option value="">Novo cliente abaixo</option>
                                @foreach($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected((string) old('lead_id') === (string) $lead->id)>
                                        {{ $lead->name }} @if($lead->email) ({{ $lead->email }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label for="customer_name">Cliente</label>
                        <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $leads[0]->name ?? '') }}" required>
                    </div>
                    <div>
                        <label for="customer_phone">Telefone do cliente</label>
                        <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone', $leads[0]->phone ?? '') }}">
                    </div>
                    <div>
                        <label for="customer_email">Email do cliente</label>
                        <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email', $leads[0]->email ?? '') }}">
                    </div>
                    <div>
                        <label for="product_name">Produto/serviço</label>
                        <input id="product_name" name="product_name" type="text" value="{{ old('product_name') }}" required>
                    </div>
                    <div>
                        <label for="amount">Valor</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', '0') }}" required>
                    </div>
                    <div>
                        <label for="status">Status inicial</label>
                        <select id="status" name="status" required>
                            @foreach($statusOptions as $slug => $label)
                                <option value="{{ $slug }}" @selected(old('status', 'novo') === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="saas-onb-actions">
                    <button class="btn btn-primary" type="submit">Salvar negócio</button>
                    <a class="btn btn-ghost" href="{{ route('onboarding.customer') }}">Voltar</a>
                </div>
            </form>
            <form method="POST" action="{{ route('onboarding.skip') }}" style="margin-top:.75rem;">
                @csrf
                <input type="hidden" name="step" value="deal">
                <button class="btn btn-ghost" type="submit">Pular por agora</button>
            </form>
        </div>
    </x-saas-onboarding-shell>
@endsection
