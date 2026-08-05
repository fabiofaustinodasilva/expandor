@extends('layouts.guest')

@section('title', 'Pagamento via PIX')

@section('content')
<style>
    .pix-wrap { max-width: 480px; margin: 0 auto; }
    .pix-card {
        padding: 1.75rem 1.5rem 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 12px 40px color-mix(in srgb, var(--text) 8%, transparent);
        border: 1px solid var(--border);
        background: var(--bg-elevated);
    }
    .pix-card h1 {
        margin: 0 0 0.35rem;
        font-size: clamp(1.35rem, 3vw, 1.65rem);
        letter-spacing: -0.02em;
        text-align: center;
    }
    .pix-meta { text-align: center; color: var(--muted); font-size: 0.92rem; margin: 0 0 1.25rem; }
    .pix-summary {
        display: flex; justify-content: space-between; gap: 1rem;
        padding: 0.85rem 1rem; margin-bottom: 1.25rem;
        border-radius: 0.75rem; background: color-mix(in srgb, var(--accent) 7%, var(--bg));
        font-size: 0.92rem;
    }
    .pix-qr {
        display: flex; justify-content: center; margin: 0.5rem 0 1.25rem;
    }
    .pix-qr img, .pix-qr canvas {
        width: min(240px, 70vw); height: auto;
        border-radius: 0.75rem; border: 1px solid var(--border);
        background: #fff; padding: 0.75rem;
    }
    .pix-copy-row { display: flex; flex-direction: column; gap: 0.55rem; margin-bottom: 1.25rem; }
    .pix-copy-row textarea {
        width: 100%; min-height: 4.5rem; resize: vertical;
        font-size: 0.78rem; line-height: 1.35; font-family: ui-monospace, monospace;
    }
    .pix-status {
        display: flex; align-items: center; justify-content: center; gap: 0.65rem;
        color: var(--muted); font-size: 0.9rem; margin: 0.5rem 0 0;
    }
    .pix-pulse {
        width: 10px; height: 10px; border-radius: 999px;
        background: var(--accent);
        box-shadow: 0 0 0 0 color-mix(in srgb, var(--accent) 55%, transparent);
        animation: pix-pulse 1.6s ease-out infinite;
        flex-shrink: 0;
    }
    @keyframes pix-pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 color-mix(in srgb, var(--accent) 45%, transparent); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px transparent; }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 transparent; }
    }
    .pix-actions { display: flex; flex-direction: column; gap: 0.55rem; margin-top: 1rem; }
    .pix-hint { text-align: center; font-size: 0.8rem; color: var(--muted); margin: 0.75rem 0 0; }
</style>

<div class="pix-wrap">
    <div class="card pix-card">
        <h1>Pagamento via PIX</h1>
        <p class="pix-meta">Escaneie o QR Code ou copie o código para pagar no app do seu banco.</p>

        <div class="pix-summary">
            <div>
                <div class="header-meta">Plano</div>
                <strong>{{ $plan?->name ?? 'Assinatura Expandor' }}</strong>
            </div>
            <div style="text-align:right;">
                <div class="header-meta">Valor</div>
                <strong>R$ {{ number_format((float) $session->amount, 2, ',', '.') }}</strong>
            </div>
        </div>

        @if($qrCodeBase64)
            <div class="pix-qr">
                <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code PIX" width="240" height="240">
            </div>
        @elseif($qrCode)
            <div class="pix-qr" id="pix-qr-fallback" data-code="{{ $qrCode }}"></div>
        @else
            <p class="muted" style="text-align:center;">QR Code indisponível. Use o código copia e cola abaixo.</p>
        @endif

        <div class="pix-copy-row">
            <label for="pix-copy-code">Código PIX Copia e Cola</label>
            <textarea id="pix-copy-code" readonly>{{ $qrCode }}</textarea>
            <button type="button" class="btn btn-primary" id="pix-copy-btn">Copiar código PIX</button>
        </div>

        <p class="pix-status" id="pix-status-line">
            <span class="pix-pulse" aria-hidden="true"></span>
            <span>Aguardando confirmação… <strong id="status-label">{{ $session->status?->value ?? 'pending' }}</strong></span>
        </p>

        @if($expiresAt)
            <p class="pix-hint">Válido até {{ $expiresAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
        @endif

        <div class="pix-actions">
            <a class="btn" href="{{ route('login') }}" style="background:transparent;border:1px solid var(--border);color:var(--text);text-align:center;">Já paguei — ir para o login</a>
        </div>
    </div>
</div>

<script>
(function () {
    const uuid = @json($session->uuid);
    const label = document.getElementById('status-label');
    const copyBtn = document.getElementById('pix-copy-btn');
    const codeEl = document.getElementById('pix-copy-code');

    if (copyBtn && codeEl) {
        copyBtn.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(codeEl.value || '');
                copyBtn.textContent = 'Código copiado!';
                setTimeout(function () { copyBtn.textContent = 'Copiar código PIX'; }, 2200);
            } catch (e) {
                codeEl.select();
                document.execCommand('copy');
                copyBtn.textContent = 'Código copiado!';
                setTimeout(function () { copyBtn.textContent = 'Copiar código PIX'; }, 2200);
            }
        });
    }

    const poll = async () => {
        try {
            const res = await fetch('/assinar/status/' + uuid, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (label) label.textContent = data.status || 'pending';
            if (data.provisioned) {
                window.location.href = data.redirect || @json(route('checkout.success', ['session' => $session->uuid]));
            }
        } catch (e) {}
    };
    poll();
    setInterval(poll, 4000);
})();
</script>
@endsection
