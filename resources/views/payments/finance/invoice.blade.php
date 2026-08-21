@extends('layouts.app')

@section('title', 'Fatura')

@section('content')
    <div class="card finance-invoice" style="max-width:720px;">
        <h1 class="page-title" style="margin-top:0;">Fatura {{ $invoice->number ?: '—' }}</h1>
        <dl class="finance-dl">
            <div><dt>Valor</dt><dd>R$ {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</dd></div>
            <div><dt>Vencimento</dt><dd>{{ optional($invoice->due_at)->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt>Status</dt><dd>{{ $invoice->status->label() }}</dd></div>
            <div><dt>Forma de pagamento</dt><dd>{{ \App\Domains\Payments\Support\BillingUiLabels::paymentMethod($invoice->payment_method) }}</dd></div>
            <div><dt>Pago em</dt><dd>{{ optional($invoice->paid_at)->format('d/m/Y H:i') ?: '—' }}</dd></div>
        </dl>

        @if($invoice->status->isPayable() && auth()->user()?->can('billing.manage', $company))
            <div class="finance-actions" style="margin-top:1rem;">
                <form method="POST" action="{{ route('company.finance.invoice.pix', $invoice) }}">
                    @csrf
                    <button class="btn btn-primary finance-cta" type="submit">Pagar com PIX</button>
                </form>
                <form method="POST" action="{{ route('company.finance.invoice.boleto', $invoice) }}">
                    @csrf
                    <button class="btn btn-ghost finance-cta" type="submit">Gerar boleto</button>
                </form>
                <form method="POST" action="{{ route('company.finance.invoice.second-copy', $invoice) }}">
                    @csrf
                    <input type="hidden" name="method" value="pix">
                    <button class="btn btn-ghost finance-cta" type="submit">Segunda via</button>
                </form>
            </div>
        @endif

        <p style="margin-top:1.25rem;"><a href="{{ route('company.finance.index') }}">← Voltar ao Financeiro</a></p>
    </div>

    <style>
        .finance-dl { margin:0; display:grid; gap:.65rem; }
        .finance-dl > div { display:grid; grid-template-columns: 11rem 1fr; gap:.75rem; }
        .finance-dl dt { margin:0; color: var(--muted, #6b7280); font-size:.9rem; }
        .finance-dl dd { margin:0; font-weight:600; }
        .finance-actions { display:flex; flex-wrap:wrap; gap:.75rem; }
        .finance-cta { min-height:44px; min-width:140px; padding:.7rem 1rem; }
        @media (max-width: 720px) {
            .finance-dl > div { grid-template-columns: 1fr; gap:.15rem; }
            .finance-actions { flex-direction: column; }
            .finance-cta { width: 100%; }
        }
    </style>
@endsection
