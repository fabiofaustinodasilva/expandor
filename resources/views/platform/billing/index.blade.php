@extends('layouts.platform')

@section('title', 'Billing')

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Billing</h1>
        <div class="header-meta">Pagamentos, assinaturas, checkouts pendentes e renovações</div>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <div class="header-meta">Trials</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($counts['trial'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Ativas</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($counts['active'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Suspensas (empresa)</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($counts['suspended'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Canceladas</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($counts['cancelled'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Inadimplentes</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($counts['past_due'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Checkouts pendentes</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($counts['pending_checkouts'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Pago no mês</div>
            <div style="font-size:1.8rem; font-weight:700;">R$ {{ number_format((float) $counts['paid_this_month'], 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <form method="GET" action="{{ route('platform.billing.index') }}" class="grid grid-2">
            <div class="form-group">
                <label for="subscription_status">Filtro assinaturas</label>
                <select class="form-control" name="subscription_status" id="subscription_status">
                    <option value="">Todas</option>
                    @foreach(['trial' => 'Trial', 'active' => 'Ativa', 'past_due' => 'Inadimplente', 'cancelled' => 'Cancelada', 'suspended' => 'Empresa suspensa'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['subscription_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="payment_status">Filtro pagamentos</label>
                <select class="form-control" name="payment_status" id="payment_status">
                    <option value="">Todos</option>
                    @foreach(['pending' => 'Pendente', 'paid' => 'Pago', 'failed' => 'Falhou', 'refunded' => 'Estornado'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="{{ route('platform.billing.index') }}">Limpar</a>
            </div>
        </form>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Renovações próximas (14 dias)</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Empresa</th>
                <th>Plano</th>
                <th>Status</th>
                <th>Próxima cobrança</th>
            </tr>
            </thead>
            <tbody>
            @forelse($upcomingRenewals as $sub)
                <tr>
                    <td>{{ $sub->company?->name ?? '—' }}</td>
                    <td>{{ $sub->plan?->name ?? '—' }}</td>
                    <td><span class="badge">{{ $sub->status }}</span></td>
                    <td>{{ optional($sub->next_billing_at)->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Nenhuma renovação nos próximos 14 dias.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Checkouts pendentes</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Empresa</th>
                <th>Comprador</th>
                <th>Plano</th>
                <th>Valor</th>
                <th>Gateway</th>
                <th>Expira</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pendingCheckouts as $checkout)
                <tr>
                    <td>{{ $checkout->company_name }}</td>
                    <td>{{ $checkout->buyer_email }}</td>
                    <td>{{ $checkout->plan?->name ?? '—' }}</td>
                    <td>R$ {{ number_format((float) $checkout->amount, 2, ',', '.') }}</td>
                    <td>{{ $checkout->gateway }}</td>
                    <td>{{ optional($checkout->expires_at)->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhum checkout pendente.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Assinaturas</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Empresa</th>
                <th>Plano</th>
                <th>Status</th>
                <th>Ciclo</th>
                <th>Próxima cobrança</th>
            </tr>
            </thead>
            <tbody>
            @forelse($subscriptions as $sub)
                <tr>
                    <td>
                        @if($sub->company)
                            <a href="{{ route('platform.companies.show', $sub->company) }}">{{ $sub->company->name }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $sub->plan?->name ?? '—' }}</td>
                    <td><span class="badge">{{ $sub->status }}</span></td>
                    <td>{{ $sub->billing_cycle ?? '—' }}</td>
                    <td>{{ optional($sub->next_billing_at)->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma assinatura encontrada.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:0.75rem;">{{ $subscriptions->links() }}</div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Pagamentos</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Empresa</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Método</th>
                <th>Gateway</th>
                <th>Pago em</th>
            </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->company?->name ?? '—' }}</td>
                    <td>R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                    <td><span class="badge">{{ $payment->status?->value ?? $payment->status }}</span></td>
                    <td>{{ $payment->method ?? '—' }}</td>
                    <td>{{ $payment->gateway }}</td>
                    <td>{{ optional($payment->paid_at)->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhum pagamento encontrado.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:0.75rem;">{{ $payments->links() }}</div>
    </div>
@endsection
