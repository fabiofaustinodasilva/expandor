@php
    $brandName = $brand ?? ($settings->title ?? 'Expandor');
    $primary = $settings->primary_color ?: '#3B82F6';
    $secondary = $settings->secondary_color ?: '#0F172A';
    $background = $settings->background_color ?: '#0B1220';
    $button = $settings->button_color ?: '#F59E0B';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido de demonstração recebido — {{ $brandName }}</title>
    <meta name="robots" content="noindex,nofollow">
    <style>
        :root {
            --mkp-primary: {{ $primary }};
            --mkp-secondary: {{ $secondary }};
            --mkp-bg: {{ $background }};
            --mkp-button: {{ $button }};
            --mkp-text: #F1F5F9;
            --mkp-muted: #94A3B8;
            --mkp-border: rgba(148, 163, 184, 0.18);
            --mkp-surface: rgba(15, 23, 42, 0.82);
            --mkp-radius: 1rem;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--mkp-button) 22%, transparent), transparent 42%),
                var(--mkp-bg);
            color: var(--mkp-text);
        }
        .wrap {
            max-width: 640px;
            margin: 0 auto;
            padding: 1.5rem 1.15rem 2.5rem;
        }
        .brand {
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.92rem;
            margin-bottom: 1.5rem;
        }
        .card {
            background: var(--mkp-surface);
            border: 1px solid var(--mkp-border);
            border-radius: var(--mkp-radius);
            padding: 1.6rem 1.35rem 1.75rem;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.28);
        }
        h1 {
            margin: 0 0 0.85rem;
            font-size: clamp(1.55rem, 5vw, 2rem);
            line-height: 1.2;
        }
        .lead {
            margin: 0 0 1.35rem;
            color: var(--mkp-muted);
            font-size: 1.02rem;
            line-height: 1.55;
        }
        .summary {
            margin: 0 0 1.5rem;
            padding: 0.95rem 1rem;
            border-radius: 0.85rem;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid var(--mkp-border);
            font-size: 0.92rem;
            color: var(--mkp-muted);
            line-height: 1.55;
        }
        .summary strong { color: var(--mkp-text); font-weight: 650; }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 3.15rem;
            padding: 0.9rem 1.15rem;
            border-radius: 0.85rem;
            font-weight: 750;
            font-size: 1.05rem;
            text-decoration: none;
            border: 1px solid transparent;
            text-align: center;
        }
        .btn-wa {
            background: #25D366;
            color: #062816;
            box-shadow: 0 10px 28px rgba(37, 211, 102, 0.28);
        }
        .btn-wa:hover { filter: brightness(1.05); }
        .btn-secondary {
            background: transparent;
            color: var(--mkp-text);
            border-color: var(--mkp-border);
        }
        .note {
            margin: 1rem 0 0;
            color: var(--mkp-muted);
            font-size: 0.86rem;
            line-height: 1.45;
        }
        @media (min-width: 640px) {
            .wrap { padding-top: 3rem; }
            .card { padding: 2rem 2rem 1.85rem; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">{{ $brandName }}</div>
        <div class="card">
            <h1>Pedido de demonstração recebido!</h1>
            <p class="lead">
                Seus dados já foram enviados para nossa equipe. Se quiser agilizar o atendimento, fale conosco agora pelo WhatsApp.
            </p>

            @if($lead)
                <div class="summary">
                    <div><strong>{{ $lead->name }}</strong> · {{ $lead->company_name }}</div>
                    <div>{{ $lead->cityState() }} · {{ $lead->sellers_count }} vendedores · {{ number_format((int) $lead->customers_count, 0, ',', '.') }} clientes</div>
                    <div>{{ \App\Domains\Marketplace\Growth\Support\BrazilianPhone::format($lead->phone) }} · {{ $lead->email }}</div>
                </div>
            @endif

            <div class="actions">
                @if($whatsappUrl)
                    <a class="btn btn-wa" href="{{ $whatsappUrl }}" target="_blank" rel="noopener" data-mkp-event="marketplace.demo_whatsapp_cta">
                        Falar agora no WhatsApp
                    </a>
                @else
                    <p class="note">Nossa equipe retornará em breve pelo WhatsApp ou e-mail informados.</p>
                @endif
                <a class="btn btn-secondary" href="{{ route('marketplace.home') }}">Continuar no site</a>
            </div>
        </div>
    </div>
</body>
</html>
