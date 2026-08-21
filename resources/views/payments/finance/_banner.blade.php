@if(!empty($currentInvoice) && $currentInvoice->status->isPayable() && ($company->status ?? null) === \App\Domains\Company\Models\Company::STATUS_ACTIVE)
    <div class="card finance-banner" style="margin-bottom:1rem; border-left:4px solid #c97800; background:rgba(201,120,0,.08);">
        <strong>Mensalidade pendente</strong>
        <p style="margin:.35rem 0 0.75rem;">
            Valor R$ {{ number_format((float) $currentInvoice->amount_due, 2, ',', '.') }}
            · vencimento {{ optional($currentInvoice->due_at)->format('d/m/Y') ?: '—' }}
            @if(!empty($withinGrace) && ($daysPastDue ?? 0) > 0)
                · {{ $daysPastDue }} dia(s) após o vencimento (período de regularização)
            @endif
        </p>
        <a class="btn btn-primary finance-cta" href="{{ route('company.finance.index') }}">Pagar agora</a>
    </div>
    <style>
        .finance-cta { min-height:44px; display:inline-flex; align-items:center; padding:.65rem 1rem; }
        @media (max-width: 720px) { .finance-cta { width:100%; justify-content:center; } }
    </style>
@endif
