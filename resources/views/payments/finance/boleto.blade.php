@extends('layouts.app')

@section('title', 'Boleto — Fatura')

@section('content')
    <div class="card finance-pay" style="max-width:640px;">
        <h1 class="page-title" style="margin-top:0;">Boleto bancário</h1>
        <p class="header-meta" style="margin-top:0;">Use a linha digitável ou abra o boleto. A confirmação ocorre após a compensação.</p>

        <dl class="finance-dl">
            <div><dt>Valor</dt><dd>R$ {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</dd></div>
            <div><dt>Vencimento</dt><dd>{{ optional($invoice->due_at)->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt>Status</dt><dd>{{ $invoice->status->label() }} · aguardando pagamento</dd></div>
        </dl>

        @if(!empty($charge['digitable_line']))
            <label for="boleto_line">Linha digitável</label>
            <textarea id="boleto_line" class="form-control" rows="2" readonly>{{ $charge['digitable_line'] }}</textarea>
            <button type="button" class="btn btn-ghost finance-cta" style="margin-top:.75rem;" id="boleto-copy-btn">Copiar linha digitável</button>
        @endif

        <div class="finance-actions" style="margin-top:1rem;">
            @if(!empty($charge['boleto_url']))
                <a class="btn btn-primary finance-cta" href="{{ $charge['boleto_url'] }}" target="_blank" rel="noopener">Abrir boleto</a>
            @endif
            <form method="POST" action="{{ route('company.finance.invoice.second-copy', $invoice) }}">
                @csrf
                <input type="hidden" name="method" value="boleto">
                <button class="btn btn-ghost finance-cta" type="submit">Segunda via</button>
            </form>
        </div>

        <p style="margin-top:1.25rem;"><a href="{{ route('company.finance.index') }}">← Voltar ao Financeiro</a></p>
    </div>

    <style>
        .finance-dl { margin:1rem 0; display:grid; gap:.65rem; }
        .finance-dl > div { display:grid; grid-template-columns: 9rem 1fr; gap:.75rem; }
        .finance-dl dt { margin:0; color: var(--muted, #6b7280); font-size:.9rem; }
        .finance-dl dd { margin:0; font-weight:600; }
        .finance-actions { display:flex; flex-wrap:wrap; gap:.75rem; }
        .finance-cta { min-height:44px; min-width:160px; padding:.7rem 1rem; display:inline-flex; align-items:center; justify-content:center; }
        @media (max-width: 720px) {
            .finance-dl > div { grid-template-columns: 1fr; gap:.15rem; }
            .finance-actions { flex-direction: column; }
            .finance-cta { width: 100%; }
        }
    </style>
    <script>
        (function () {
            var btn = document.getElementById('boleto-copy-btn');
            var field = document.getElementById('boleto_line');
            if (!btn || !field) return;
            btn.addEventListener('click', function () {
                navigator.clipboard.writeText(field.value).then(function () {
                    btn.textContent = 'Linha copiada';
                    setTimeout(function () { btn.textContent = 'Copiar linha digitável'; }, 2000);
                }).catch(function () {
                    field.select();
                    document.execCommand('copy');
                });
            });
        })();
    </script>
@endsection
