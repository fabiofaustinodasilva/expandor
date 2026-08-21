@extends('layouts.app')

@section('title', 'Pagamento pendente')

@section('content')
    <div class="card" style="max-width:720px; margin:0 auto;">
        <h1 class="page-title" style="margin-top:0;">Pagamento pendente</h1>
        <p>Identificamos uma mensalidade em aberto. Regularize o pagamento para restabelecer o acesso completo ao Expandor.</p>

        @if($plan)
            <p><strong>Plano:</strong> {{ $plan->name }}</p>
            <p><strong>Valor:</strong> R$ {{ number_format((float) ($currentInvoice->amount_due ?? $plan->price), 2, ',', '.') }}</p>
        @endif
        @if($currentInvoice)
            <p><strong>Vencimento:</strong> {{ optional($currentInvoice->due_at)->format('d/m/Y') }}</p>
            <p><strong>Dias em atraso:</strong> {{ $daysPastDue }}</p>

            @if(auth()->user()?->can('billing.manage', $company))
                <form method="POST" action="{{ route('company.finance.invoice.pix', $currentInvoice) }}" style="display:inline-block; margin-right:.5rem;">
                    @csrf
                    <button class="btn btn-primary" type="submit">Pagar com PIX</button>
                </form>
                <form method="POST" action="{{ route('company.finance.invoice.boleto', $currentInvoice) }}" style="display:inline-block;">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Gerar boleto</button>
                </form>
            @endif
        @endif

        <div style="margin-top:1.25rem; display:flex; gap:.75rem; flex-wrap:wrap;">
            <a class="btn btn-ghost" href="{{ route('company.finance.index') }}">Histórico financeiro</a>
            <a class="btn btn-ghost" href="mailto:{{ config('mail.from.address') }}">Suporte</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-danger" type="submit">Sair</button>
            </form>
        </div>
    </div>
@endsection
