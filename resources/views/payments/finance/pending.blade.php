@extends('layouts.app')

@section('title', 'Pagamento pendente')

@section('content')
    <div class="card finance-pending" style="max-width:720px; margin:0 auto;">
        <h1 class="page-title" style="margin-top:0;">Pagamento pendente</h1>
        <p>Identificamos uma mensalidade em aberto. Regularize o pagamento para restabelecer o acesso completo ao Expandor.</p>

        @if($plan || $currentInvoice)
            <dl class="finance-dl">
                @if($plan)
                    <div><dt>Plano</dt><dd>{{ $plan->name }}</dd></div>
                    <div><dt>Valor</dt><dd>R$ {{ number_format((float) ($currentInvoice->amount_due ?? $plan->price), 2, ',', '.') }}</dd></div>
                @endif
                @if($currentInvoice)
                    <div><dt>Vencimento</dt><dd>{{ optional($currentInvoice->due_at)->format('d/m/Y') ?: '—' }}</dd></div>
                    <div><dt>Dias em atraso</dt><dd>{{ $daysPastDue }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $currentInvoice->status->label() }}</dd></div>
                @endif
            </dl>
        @endif

        @if($currentInvoice && auth()->user()?->can('billing.manage', $company))
            <div class="finance-actions">
                <form method="POST" action="{{ route('company.finance.invoice.pix', $currentInvoice) }}">
                    @csrf
                    <button class="btn btn-primary finance-cta" type="submit">Pagar com PIX</button>
                </form>
                <form method="POST" action="{{ route('company.finance.invoice.boleto', $currentInvoice) }}">
                    @csrf
                    <button class="btn btn-ghost finance-cta" type="submit">Gerar boleto</button>
                </form>
            </div>
        @endif

        <div class="finance-actions" style="margin-top:1.25rem;">
            <a class="btn btn-ghost finance-cta" href="{{ route('company.finance.index') }}">Histórico financeiro</a>
            <a class="btn btn-ghost finance-cta" href="mailto:{{ config('mail.from.address') }}">Falar com suporte</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-danger finance-cta" type="submit">Sair</button>
            </form>
        </div>
    </div>

    <style>
        .finance-dl { margin:1rem 0; display:grid; gap:.65rem; }
        .finance-dl > div { display:grid; grid-template-columns: 10rem 1fr; gap:.75rem; }
        .finance-dl dt { margin:0; color: var(--muted, #6b7280); font-size:.9rem; }
        .finance-dl dd { margin:0; font-weight:600; }
        .finance-actions { display:flex; flex-wrap:wrap; gap:.75rem; }
        .finance-cta { min-height:44px; min-width:140px; padding:.7rem 1rem; display:inline-flex; align-items:center; justify-content:center; }
        @media (max-width: 720px) {
            .finance-dl > div { grid-template-columns: 1fr; gap:.15rem; }
            .finance-actions { flex-direction: column; }
            .finance-cta { width: 100%; }
        }
    </style>
@endsection
