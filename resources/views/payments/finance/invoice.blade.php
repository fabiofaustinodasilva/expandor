@extends('layouts.app')

@section('title', 'Fatura '.$invoice->number)

@section('content')
    <div class="card" style="max-width:720px;">
        <h1 class="page-title" style="margin-top:0;">Fatura {{ $invoice->number }}</h1>
        <p><strong>Valor:</strong> R$ {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</p>
        <p><strong>Vencimento:</strong> {{ optional($invoice->due_at)->format('d/m/Y') }}</p>
        <p><strong>Status:</strong> {{ $invoice->status->label() }}</p>
        <p><strong>Gateway:</strong> {{ $invoice->gateway ?: '—' }}</p>
        <p><strong>Referência:</strong> {{ $invoice->gateway_invoice_id ?: '—' }}</p>

        @if($invoice->status->isPayable() && auth()->user()?->can('billing.manage', $company))
            <form method="POST" action="{{ route('company.finance.invoice.pix', $invoice) }}" style="display:inline-block; margin-right:.5rem;">
                @csrf
                <button class="btn btn-primary" type="submit">Pagar com PIX</button>
            </form>
            <form method="POST" action="{{ route('company.finance.invoice.boleto', $invoice) }}" style="display:inline-block; margin-right:.5rem;">
                @csrf
                <button class="btn btn-ghost" type="submit">Gerar boleto</button>
            </form>
            <form method="POST" action="{{ route('company.finance.invoice.second-copy', $invoice) }}" style="display:inline-block;">
                @csrf
                <input type="hidden" name="method" value="pix">
                <button class="btn btn-ghost" type="submit">Segunda via PIX</button>
            </form>
        @endif

        <p style="margin-top:1rem;"><a href="{{ route('company.finance.index') }}">← Voltar ao Financeiro</a></p>
    </div>
@endsection
