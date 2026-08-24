@extends('layouts.app')

@section('title', 'Financeiro')

@section('content')
    @include('payments.finance._banner')

    <div class="finance-page">
        <div class="finance-header">
            <div>
                <h1 class="page-title" style="margin:0;">Financeiro</h1>
                <div class="header-meta">{{ $company->name }}</div>
            </div>
            <a class="btn btn-ghost" href="{{ route('company.subscription.show') }}">Gerenciar plano</a>
        </div>

        <div class="finance-grid">
            <div class="card">
                <h2 style="margin-top:0;">Minha assinatura</h2>
                @if($subscription && $plan)
                    @if($fidelity && ($fidelity['term_completed'] ?? false))
                        <div class="finance-badge finance-badge--success">Fidelidade concluída</div>
                    @endif
                    <dl class="finance-dl">
                        <div><dt>Plano</dt><dd>{{ $plan->name }}</dd></div>
                        <div><dt>Valor mensal</dt><dd>R$ {{ number_format((float) $subscription->monthlyAmount(), 2, ',', '.') }}</dd></div>
                        <div><dt>Status</dt><dd>{{ \App\Domains\Payments\Support\BillingUiLabels::subscriptionStatus($subscription->status) }}</dd></div>
                        <div><dt>Próximo vencimento</dt><dd>{{ optional($currentInvoice?->due_at ?? $subscription->next_billing_at)->format('d/m/Y') ?: '—' }}</dd></div>
                        <div><dt>Início do contrato</dt><dd>{{ optional($subscription->contract_started_at)->format('d/m/Y') ?: '—' }}</dd></div>
                        @if($fidelity && ($fidelity['has_term'] ?? false))
                            <div><dt>Fidelidade mínima</dt><dd>{{ $fidelity['months'] }} meses</dd></div>
                            <div><dt>Fim da fidelidade</dt><dd>{{ optional($fidelity['ends_at'])->format('d/m/Y') }}</dd></div>
                            @unless($fidelity['term_completed'] ?? false)
                                <div><dt>Progresso</dt><dd>{{ $fidelity['progress_label'] }}</dd></div>
                            @endunless
                        @else
                            <div><dt>Fidelidade</dt><dd>{{ $fidelity['progress_label'] ?? 'Sem fidelidade mínima' }}</dd></div>
                        @endif
                    </dl>
                    @if(!empty($fidelity['continuity_note']))
                        <p class="finance-note">{{ $fidelity['continuity_note'] }}</p>
                    @endif
                @else
                    <p>Nenhuma assinatura encontrada.</p>
                @endif
            </div>

            <div class="card">
                <h2 style="margin-top:0;">Fatura atual</h2>
                @if($currentInvoice)
                    <dl class="finance-dl">
                        <div><dt>Valor</dt><dd>R$ {{ number_format((float) $currentInvoice->amount_due, 2, ',', '.') }}</dd></div>
                        <div><dt>Vencimento</dt><dd>{{ optional($currentInvoice->due_at)->format('d/m/Y') ?: '—' }}</dd></div>
                        <div><dt>Status</dt><dd>{{ $currentInvoice->status->label() }}</dd></div>
                    </dl>
                    @if(auth()->user()?->can('billing.manage', $company) && $currentInvoice->status->isPayable())
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
                @elseif($nextChargePreview)
                    <dl class="finance-dl">
                        <div><dt>Próxima cobrança</dt><dd>{{ $nextChargePreview['due_at']->format('d/m/Y') }}</dd></div>
                        <div><dt>Valor</dt><dd>R$ {{ number_format((float) $nextChargePreview['amount'], 2, ',', '.') }}</dd></div>
                    </dl>
                    @if(!($nextChargePreview['within_window'] ?? false))
                        <p class="finance-note">
                            Disponível para pagamento a partir de {{ $nextChargePreview['available_from']->format('d/m/Y') }}.
                        </p>
                    @endif
                @else
                    <p>Nenhuma fatura em aberto.</p>
                @endif
            </div>
        </div>

        @if(isset($upcomingCharges) && $upcomingCharges->isNotEmpty())
            <div class="card" style="margin-bottom:1rem;">
                <h2 style="margin-top:0;">Próximas mensalidades</h2>
                <div class="finance-table-wrap">
                    <table class="table finance-table">
                        <thead>
                        <tr>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($upcomingCharges as $charge)
                            <tr>
                                <td>{{ $charge['due_at']->format('d/m/Y') }}</td>
                                <td>R$ {{ number_format((float) $charge['amount'], 2, ',', '.') }}</td>
                                <td>{{ $charge['status_label'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="finance-note">Mensalidades programadas são projeções. PIX e boleto ficam disponíveis quando a fatura real for gerada.</p>
            </div>
        @endif

        <div class="card">
            <h2 style="margin-top:0;">Histórico de faturas</h2>
            <div class="finance-table-wrap">
                <table class="table finance-table">
                    <thead>
                    <tr>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Forma</th>
                        <th>Pago em</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($overview->invoices as $row)
                        <tr>
                            <td>{{ optional($row->due_at)->format('d/m/Y') ?: '—' }}</td>
                            <td>R$ {{ number_format((float) $row->amount_due, 2, ',', '.') }}</td>
                            <td>{{ $row->status->label() }}</td>
                            <td>{{ \App\Domains\Payments\Support\BillingUiLabels::paymentMethod($row->payment_method) }}</td>
                            <td>{{ optional($row->paid_at)->format('d/m/Y H:i') ?: '—' }}</td>
                            <td><a class="finance-link" href="{{ route('company.finance.invoice.show', $row) }}">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Sem faturas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .finance-page { max-width: 1100px; width: 100%; box-sizing: border-box; }
        .finance-header { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; flex-wrap:wrap; }
        .finance-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem; }
        .finance-dl { margin:0; display:grid; gap:.65rem; }
        .finance-dl > div { display:grid; grid-template-columns: 11rem minmax(0, 1fr); gap:.75rem; align-items:baseline; }
        .finance-dl dt { margin:0; color: var(--muted, #6b7280); font-size:.9rem; }
        .finance-dl dd { margin:0; font-weight:600; overflow-wrap:anywhere; }
        .finance-note { margin:.85rem 0 0; color: var(--muted, #9aa3b5); font-size:.9rem; line-height:1.45; }
        .finance-badge { display:inline-block; margin-bottom:.75rem; padding:.25rem .6rem; border-radius:999px; font-size:.78rem; font-weight:700; }
        .finance-badge--success { background: rgba(34,197,94,.15); color:#16a34a; }
        .finance-actions { display:flex; flex-wrap:wrap; gap:.75rem; margin-top:1rem; }
        .finance-cta { min-height:44px; min-width:140px; padding:.7rem 1rem; box-sizing:border-box; }
        .finance-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; max-width:100%; }
        .finance-table { min-width:560px; }
        .finance-link { font-weight:600; }
        @media (max-width: 720px) {
            .finance-page { max-width: 100%; overflow-x: hidden; }
            .finance-grid { grid-template-columns: 1fr; }
            .finance-dl > div { grid-template-columns: 1fr; gap:.15rem; }
            .finance-actions { flex-direction: column; }
            .finance-cta { width: 100%; min-height: 44px; }
            .finance-table { min-width: 480px; }
        }
    </style>
@endsection
