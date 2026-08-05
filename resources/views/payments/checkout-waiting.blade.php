@extends('layouts.guest')

@section('title', 'Aguardando pagamento')

@section('content')
    <div class="card" style="max-width:560px;">
        <h1 style="margin-top:0;">Aguardando confirmação</h1>
        <p class="muted">
            @if($session)
                Estamos aguardando a confirmação do gateway de pagamento para
                <strong>{{ $session->company_name }}</strong>.
                Assim que o PIX/cartão for aprovado, sua conta será provisionada automaticamente.
            @else
                Sessão de checkout não encontrada. Se você acabou de pagar, aguarde alguns segundos e atualize.
            @endif
        </p>

        <div id="status-box" class="muted" style="margin:1rem 0;">
            Status: <strong id="status-label">{{ $session?->status?->value ?? 'pending' }}</strong>
        </div>

        <a class="btn" href="{{ route('login') }}" style="margin-bottom:0.75rem;">Ir para o login</a>
        <a class="btn" href="{{ route('marketplace.plans') }}" style="background:transparent;border:1px solid var(--border);color:var(--text);">Voltar aos planos</a>
    </div>

    @if($session)
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
                        if (data.provisioned && data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } catch (e) {}
                };
                poll();
                setInterval(poll, 4000);
            })();
        </script>
    @endif
@endsection
