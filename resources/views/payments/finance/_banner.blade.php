@if(!empty($currentInvoice) && $currentInvoice->status->isPayable() && ($company->status ?? null) === \App\Domains\Company\Models\Company::STATUS_ACTIVE)
    <div class="card" style="margin-bottom:1rem; border-left:4px solid #c97800; background:rgba(201,120,0,.08);">
        <strong>Mensalidade pendente</strong>
        <p style="margin:.35rem 0;">
            Valor R$ {{ number_format((float) $currentInvoice->amount_due, 2, ',', '.') }}
            · vencimento {{ optional($currentInvoice->due_at)->format('d/m/Y') }}
            @if(!empty($withinGrace) && $daysPastDue > 0)
                · em tolerância ({{ $daysPastDue }} dia(s) após o vencimento)
            @endif
        </p>
        <a class="btn btn-primary" href="{{ route('company.finance.index') }}">Pagar agora</a>
    </div>
@endif
