@extends('layouts.app')

@section('title', 'Financeiro')

@section('content')
    @include('payments.finance._banner')

    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Financeiro</h1>
            <div class="header-meta">{{ $company->name }}</div>
        </div>
        <a class="btn btn-ghost" href="{{ route('company.subscription.show') }}">Assinatura (legado)</a>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <h2 style="margin-top:0;">Minha assinatura</h2>
            @if($subscription && $plan)
                <p><strong>Plano:</strong> {{ $plan->name }}</p>
                <p><strong>Valor mensal:</strong> R$ {{ number_format((float) $plan->price, 2, ',', '.') }}</p>
                <p><strong>Status:</strong> {{ $subscription->status }}</p>
                <p><strong>Próximo vencimento:</strong> {{ optional($subscription->next_billing_at)->format('d/m/Y') ?: '—' }}</p>
                <p><strong>Início do contrato:</strong> {{ optional($subscription->contract_started_at)->format('d/m/Y') ?: '—' }}</p>
                @if($fidelity && $fidelity['has_term'])
                    <p><strong>Fidelidade mínima:</strong> {{ $fidelity['months'] }} meses</p>
                    <p><strong>Fim da fidelidade:</strong> {{ optional($fidelity['ends_at'])->format('d/m/Y') }}</p>
                    <p><strong>Progresso:</strong> {{ $fidelity['progress_label'] }}</p>
                @else
                    <p><strong>Fidelidade:</strong> {{ $fidelity['progress_label'] ?? '—' }}</p>
                @endif
            @else
                <p>Nenhuma assinatura encontrada.</p>
            @endif
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Fatura atual</h2>
            @if($currentInvoice)
                <p><strong>Valor:</strong> R$ {{ number_format((float) $currentInvoice->amount_due, 2, ',', '.') }}</p>
                <p><strong>Vencimento:</strong> {{ optional($currentInvoice->due_at)->format('d/m/Y') }}</p>
                <p><strong>Status:</strong> {{ $currentInvoice->status->label() }}</p>
                @if(auth()->user()?->can('billing.manage', $company) && $currentInvoice->status->isPayable())
                    <form method="POST" action="{{ route('company.finance.invoice.pix', $currentInvoice) }}" style="display:inline-block; margin-right:.5rem;">
                        @csrf
                        <button class="btn btn-primary" type="submit">Pagar com PIX</button>
                    </form>
                    <form method="POST" action="{{ route('company.finance.invoice.boleto', $currentInvoice) }}" style="display:inline-block;">
                        @csrf
                        <button class="btn btn-ghost" type="submit">Gerar boleto</button>
                    </form>
                @endif
            @else
                <p>Nenhuma fatura em aberto.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Histórico de faturas</h2>
        <div style="overflow-x:auto;">
            <table class="table">
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
                        <td>{{ $row->payment_method ?: '—' }}</td>
                        <td>{{ optional($row->paid_at)->format('d/m/Y H:i') ?: '—' }}</td>
                        <td><a href="{{ route('company.finance.invoice.show', $row) }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Sem faturas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
