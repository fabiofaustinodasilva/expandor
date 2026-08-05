@extends('layouts.guest')

@section('title', $provisioned ?? false ? 'Acesso liberado' : 'Pagamento recebido')

@section('content')
    <div class="card" style="max-width:560px;">
        @if($provisioned ?? false)
            <h1 style="margin-top:0;">Acesso liberado</h1>
            <p class="muted">
                @if($session)
                    O pagamento de <strong>{{ $session->company_name }}</strong> foi confirmado.
                    Sua empresa já está ativa. Use o e-mail <strong>{{ $session->buyer_email }}</strong> para entrar.
                @else
                    Seu pagamento foi confirmado e o acesso já está disponível.
                @endif
            </p>
            <a class="btn" href="{{ route('login') }}">Ir para o login</a>
        @else
            <h1 style="margin-top:0;">Pagamento em processamento</h1>
            <p class="muted">
                @if($session)
                    Recebemos o retorno do checkout para <strong>{{ $session->company_name }}</strong>.
                    Assim que o Mercado Pago confirmar o pagamento, sua empresa será provisionada automaticamente
                    e o acesso será enviado para <strong>{{ $session->buyer_email }}</strong>.
                @else
                    Assim que o pagamento for confirmado, enviaremos o acesso por e-mail.
                @endif
            </p>

            <div id="status-box" class="muted" style="margin:1rem 0;">
                Status: <strong id="status-label">{{ $session?->status?->value ?? 'pending' }}</strong>
            </div>

            <a class="btn" href="{{ route('login') }}" style="margin-bottom:0.75rem;">Ir para o login</a>
            <a class="btn" href="{{ route('marketplace.plans') }}" style="background:transparent;border:1px solid var(--border);color:var(--text);">Voltar aos planos</a>
        @endif
    </div>

    @if(($session ?? null) && ! ($provisioned ?? false))
        <script>
            (function () {
                const uuid = @json($session->uuid);
                const label = document.getElementById('status-label');
                const poll = async () => {
                    try {
                        const res = await fetch(`/assinar/status/${uuid}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (label) label.textContent = data.status || 'pending';
                        if (data.provisioned) {
                            window.location.reload();
                        }
                    } catch (e) {}
                };
                poll();
                setInterval(poll, 4000);
            })();
        </script>
    @endif
@endsection
