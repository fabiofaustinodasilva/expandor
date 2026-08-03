@extends('layouts.app')

@section('title', 'Minha Assinatura')

@section('content')
    @php
        $subscription = $overview->subscription;
        $plan = $subscription?->plan;
    @endphp

    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Minha Assinatura</h1>
            <div class="header-meta">{{ $overview->company->name }}</div>
        </div>
        <a class="btn btn-ghost" href="{{ route('company.plan.show') }}">Plano e uso</a>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <h2 style="margin-top:0;">Plano atual</h2>
            @if($subscription && $plan)
                <p><strong>Plano:</strong> {{ $plan->name }}</p>
                <p><strong>Status:</strong> {{ $subscription->status }}</p>
                <p><strong>Valor:</strong> R$ {{ number_format((float) $plan->price, 2, ',', '.') }}/mês</p>
                <p><strong>Próxima cobrança:</strong> {{ optional($subscription->next_billing_at)->format('d/m/Y H:i') ?: '—' }}</p>
                <p><strong>Ciclo:</strong> {{ $subscription->billing_cycle ?: 'monthly' }}</p>
            @else
                <p>Nenhuma assinatura ativa encontrada.</p>
            @endif
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Ações</h2>
            @if($subscription && auth()->user()?->hasPermission('billing.manage'))
                <form method="POST" action="{{ route('company.subscription.upgrade') }}" style="margin-bottom:1rem;">
                    @csrf
                    <div class="form-group">
                        <label for="upgrade_plan_id">Upgrade</label>
                        <select class="form-control" id="upgrade_plan_id" name="plan_id">
                            @foreach($plans->where('price', '>', (float) ($plan?->price ?? 0)) as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} — R$ {{ number_format((float) $item->price, 2, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Fazer upgrade</button>
                </form>

                <form method="POST" action="{{ route('company.subscription.downgrade') }}" style="margin-bottom:1rem;">
                    @csrf
                    <div class="form-group">
                        <label for="downgrade_plan_id">Downgrade</label>
                        <select class="form-control" id="downgrade_plan_id" name="plan_id">
                            @foreach($plans->where('price', '<', (float) ($plan?->price ?? 0)) as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} — R$ {{ number_format((float) $item->price, 2, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-ghost" type="submit">Fazer downgrade</button>
                </form>

                <form method="POST" action="{{ route('company.subscription.cancel') }}" onsubmit="return confirm('Cancelar assinatura?');">
                    @csrf
                    <button class="btn btn-danger" type="submit">Cancelar assinatura</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">Pagamentos</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Método</th>
                    <th>Valor</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($overview->payments as $payment)
                    <tr>
                        <td>{{ optional($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ strtoupper($payment->method ?? '—') }}</td>
                        <td>R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                        <td>{{ $payment->status->value ?? $payment->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhum pagamento registrado.</td></tr>
                @endforelse
                </tbody>
            </table>
            <p class="header-meta" style="margin-top:0.75rem;">Métodos suportados: PIX, Boleto e Cartão.</p>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Faturas</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Número</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Pago em</th>
                </tr>
                </thead>
                <tbody>
                @forelse($overview->invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->number }}</td>
                        <td>R$ {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</td>
                        <td>{{ $invoice->status->value ?? $invoice->status }}</td>
                        <td>{{ optional($invoice->paid_at)->format('d/m/Y') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhuma fatura registrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
