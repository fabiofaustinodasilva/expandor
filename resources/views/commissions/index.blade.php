@extends('layouts.operational')

@php
    $financeTitle = ! empty($isManager) ? 'Financeiro' : 'Comissão';
@endphp

@section('title', $financeTitle)

@section('page')
    <x-client.page-header
        :title="$financeTitle"
        :description="$isManager ? 'Comissões da equipe, aprovações e pagamentos' : 'Suas vendas e comissão acumulada no período'"
    >
        @if($isManager)
            <x-client.secondary-button :href="route('commissions.products.index')">Produtos / Estoque</x-client.secondary-button>
        @endif
    </x-client.page-header>

    <div class="grid grid-4" style="margin-bottom:1.25rem;">
        <div class="card">
            <div class="header-meta">Comissão acumulada</div>
            <div class="stat-value" style="font-size:1.5rem;">R$ {{ number_format($summary['total_amount'], 2, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Quantidade de vendas</div>
            <div class="stat-value" style="font-size:1.5rem;">{{ $summary['sales_count'] }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Pendente</div>
            <div class="stat-value" style="font-size:1.5rem;">R$ {{ number_format($summary['pending_amount'], 2, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Aprovada / Paga</div>
            <div class="stat-value" style="font-size:1.35rem;">
                R$ {{ number_format($summary['approved_amount'], 2, ',', '.') }}
                <span class="header-meta" style="display:block;font-size:.85rem;font-weight:500;">
                    Paga: R$ {{ number_format($summary['paid_amount'], 2, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <x-client.crud-toolbar>
        <x-slot:filters>
            <details class="client-filters-collapsible">
                <summary>Filtros</summary>
                <div class="client-filters-collapsible__body">
                    <form method="GET" action="{{ route('commissions.index') }}">
                        <div class="grid grid-4" style="gap:.75rem;">
                            <div>
                                <label for="date_from">De</label>
                                <input class="form-control" type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}">
                            </div>
                            <div>
                                <label for="date_to">Até</label>
                                <input class="form-control" type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}">
                            </div>
                            @if($isManager)
                                <div>
                                    <label for="user_id">Vendedor</label>
                                    <select class="form-control" id="user_id" name="user_id">
                                        <option value="">Todos</option>
                                        @foreach($sellers as $seller)
                                            <option value="{{ $seller->id }}" @selected((string) $filters['user_id'] === (string) $seller->id)>
                                                {{ $seller->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="campaign_id">Campanha</label>
                                    <select class="form-control" id="campaign_id" name="campaign_id">
                                        <option value="">Todas</option>
                                        @foreach($campaigns as $campaign)
                                            <option value="{{ $campaign->id }}" @selected((string) $filters['campaign_id'] === (string) $campaign->id)>
                                                {{ $campaign->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label for="product_id">Produto</label>
                                <select class="form-control" id="product_id" name="product_id">
                                    <option value="">Todos</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected((string) $filters['product_id'] === (string) $product->id)>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Todos</option>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="display:flex;align-items:end;">
                                <button class="btn btn-primary client-btn" type="submit">Filtrar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </details>
        </x-slot:filters>
    </x-client.crud-toolbar>

    <div class="card client-data-table" style="overflow-x:auto;">
        <table class="table" style="width:100%;">
            <thead>
            <tr>
                @if($isManager)<th>Vendedor</th>@endif
                <th>Cliente / Ponto</th>
                <th>Produto</th>
                <th>Qtd</th>
                <th>Comissão (R$)</th>
                <th>Status</th>
                <th>Data</th>
                @if($isManager)<th>Ações</th>@endif
            </tr>
            </thead>
            <tbody>
            @forelse($commissions as $row)
                @php
                    $address = $row->visit?->property?->address;
                    $client = $address
                        ? trim(($address->street ?? '').' '.($address->number ?? ''))
                        : 'Visita #'.$row->visit_id;
                    $statusClass = match ($row->status->value) {
                        'approved' => 'comm-status-approved',
                        'paid' => 'comm-status-paid',
                        default => 'comm-status-pending',
                    };
                @endphp
                <tr>
                    @if($isManager)<td>{{ $row->user?->name }}</td>@endif
                    <td>{{ $client !== '' ? $client : '—' }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->quantity }}</td>
                    <td>R$ {{ number_format((float) $row->commission_amount, 2, ',', '.') }}</td>
                    <td><span class="comm-status {{ $statusClass }}">{{ $row->status->label() }}</span></td>
                    <td>{{ optional($row->earned_at)->format('d/m/Y') }}</td>
                    @if($isManager)
                        <td style="white-space:nowrap;">
                            @if($row->status->value === 'pending')
                                <form method="POST" action="{{ route('commissions.approve', $row) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-ghost" type="submit">Aprovar</button>
                                </form>
                            @endif
                            @if(in_array($row->status->value, ['pending', 'approved'], true))
                                <form method="POST" action="{{ route('commissions.pay', $row) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">Marcar pago</button>
                                </form>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $isManager ? 8 : 6 }}">Nenhuma comissão no período.</td></tr>
            @endforelse
            </tbody>
        </table>
        <x-client.pagination-bar :paginator="$commissions" />
    </div>

    <style>
        .comm-status {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: .5rem;
            font-size: .78rem;
            font-weight: 700;
        }
        .comm-status-pending { background: rgba(245, 158, 11, .18); color: #fbbf24; }
        .comm-status-approved { background: rgba(56, 189, 248, .18); color: #38bdf8; }
        .comm-status-paid { background: rgba(52, 211, 153, .18); color: #34d399; }
    </style>
@endsection
