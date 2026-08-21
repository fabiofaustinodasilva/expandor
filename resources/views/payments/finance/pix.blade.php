@extends('layouts.app')

@section('title', 'PIX — Fatura')

@section('content')
    <div class="card finance-pay" style="max-width:640px;">
        <h1 class="page-title" style="margin-top:0;">Pagar com PIX</h1>
        <p class="header-meta" style="margin-top:0;">Escaneie o QR Code ou copie o código. O pagamento é confirmado automaticamente após a compensação.</p>

        <dl class="finance-dl">
            <div><dt>Valor</dt><dd>R$ {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</dd></div>
            <div><dt>Vencimento</dt><dd>{{ optional($invoice->due_at)->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt>Status</dt><dd>{{ $invoice->status->label() }} · aguardando pagamento</dd></div>
        </dl>

        @if(!empty($charge['qr_base64']))
            @php
                $qrMime = str_starts_with((string) $charge['qr_base64'], 'PHN2Zy') ? 'image/svg+xml' : 'image/png';
            @endphp
            <img class="finance-qr" src="data:{{ $qrMime }};base64,{{ $charge['qr_base64'] }}" alt="QR Code PIX" width="240" height="240">
        @elseif(!empty($charge['qr_code']))
            <p class="header-meta">Use o código copia e cola abaixo no aplicativo do banco.</p>
        @endif

        @if(!empty($charge['qr_code']))
            <label for="pix_copy">PIX Copia e Cola</label>
            <textarea id="pix_copy" class="form-control" rows="4" readonly>{{ $charge['qr_code'] }}</textarea>
            <button type="button" class="btn btn-primary finance-cta" style="margin-top:.75rem;" id="pix-copy-btn">Copiar código PIX</button>
            <p class="header-meta" style="margin-top:.5rem;">Abra o app do banco, escolha PIX e cole o código.</p>
        @endif

        <p style="margin-top:1.25rem;"><a href="{{ route('company.finance.index') }}">← Voltar ao Financeiro</a></p>
    </div>

    <style>
        .finance-dl { margin:1rem 0; display:grid; gap:.65rem; }
        .finance-dl > div { display:grid; grid-template-columns: 9rem 1fr; gap:.75rem; }
        .finance-dl dt { margin:0; color: var(--muted, #6b7280); font-size:.9rem; }
        .finance-dl dd { margin:0; font-weight:600; }
        .finance-qr { max-width:240px; width:100%; height:auto; display:block; margin:1rem 0; border-radius:8px; background:#fff; padding:.5rem; }
        .finance-cta { min-height:44px; min-width:160px; padding:.7rem 1rem; }
        @media (max-width: 720px) {
            .finance-dl > div { grid-template-columns: 1fr; gap:.15rem; }
            .finance-cta { width: 100%; }
        }
    </style>
    <script>
        (function () {
            var btn = document.getElementById('pix-copy-btn');
            var field = document.getElementById('pix_copy');
            if (!btn || !field) return;
            btn.addEventListener('click', function () {
                navigator.clipboard.writeText(field.value).then(function () {
                    btn.textContent = 'Código copiado';
                    setTimeout(function () { btn.textContent = 'Copiar código PIX'; }, 2000);
                }).catch(function () {
                    field.select();
                    document.execCommand('copy');
                    btn.textContent = 'Código copiado';
                });
            });
        })();
    </script>
@endsection
