@extends('layouts.operational')

@section('title', 'Produtos / Estoque')

@section('page')
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Produtos / Estoque</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Catálogo comercial, {{ mb_strtolower($commercial::commissionPerSale()) }} e controle de estoque.</p>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <a class="btn btn-ghost" href="{{ route('commissions.index') }}">Ver comissões</a>
            <a class="btn btn-primary" href="{{ route('commissions.products.create') }}">Novo produto</a>
        </div>
    </div>

    @if($lowStock->isNotEmpty())
        <div class="alert alert-warning" style="margin-bottom:1rem;">
            <strong>Alerta de estoque:</strong>
            @foreach($lowStock as $item)
                {{ $item->name }} ({{ $item->stock_quantity }}/mín. {{ $item->minimum_stock }})@if(! $loop->last), @endif
            @endforeach
        </div>
    @endif

    <form method="GET" class="card" style="margin-bottom:1rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:end;">
        <div>
            <label for="date_from">Vendidos de</label>
            <input class="form-control" type="date" id="date_from" name="date_from" value="{{ $dateFrom }}">
        </div>
        <div>
            <label for="date_to">Até</label>
            <input class="form-control" type="date" id="date_to" name="date_to" value="{{ $dateTo }}">
        </div>
        <button class="btn btn-ghost" type="submit">Atualizar</button>
    </form>

    <div class="card" style="overflow-x:auto;margin-bottom:1.25rem;">
        <table class="table" style="width:100%;">
            <thead>
            <tr>
                <th>Produto</th>
                <th>Preço (R$)</th>
                <th>Comissão (R$)</th>
                <th>Estoque atual</th>
                <th>Estoque mínimo</th>
                <th>Vendidos (período)</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td>
                        <div style="display:flex; gap:.65rem; align-items:center;">
                            @if($product->imageUrl())
                                <img src="{{ $product->imageUrl() }}" alt="" style="width:40px;height:40px;border-radius:.55rem;object-fit:cover;border:1px solid var(--border);">
                            @endif
                            <div>
                                <strong>{{ $product->name }}</strong>
                                @if($product->stock_control)
                                    <div class="header-meta">Controla estoque</div>
                                @else
                                    <div class="header-meta">Sem controle de estoque</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>R$ {{ number_format((float) $product->price, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format((float) $product->commission_amount, 2, ',', '.') }}</td>
                    <td>{{ $product->stock_control ? $product->stock_quantity : 'Sem controle' }}</td>
                    <td>{{ $product->stock_control ? $product->minimum_stock : '—' }}</td>
                    <td>{{ $product->sold_in_period }}</td>
                    <td>{{ $product->status === 'active' ? 'Ativo' : 'Inativo' }}</td>
                    <td style="white-space:nowrap;">
                        <a class="btn btn-ghost" href="{{ route('commissions.products.edit', $product) }}">Editar</a>
                        @if($product->stock_control)
                            <details style="display:inline-block;margin-left:.25rem;">
                                <summary class="btn btn-ghost" style="cursor:pointer;">Estoque</summary>
                                <div class="card" style="position:absolute;z-index:5;min-width:220px;margin-top:.35rem;">
                                    <form method="POST" action="{{ route('commissions.products.stock.entry', $product) }}" style="margin-bottom:.5rem;">
                                        @csrf
                                        <label>Entrada (+)</label>
                                        <input class="form-control" type="number" name="quantity" min="1" required>
                                        <input class="form-control" type="text" name="notes" placeholder="Obs." style="margin-top:.35rem;">
                                        <button class="btn btn-primary" type="submit" style="margin-top:.35rem;">Registrar</button>
                                    </form>
                                    <form method="POST" action="{{ route('commissions.products.stock.adjust', $product) }}">
                                        @csrf
                                        <label>Ajuste (+/-)</label>
                                        <input class="form-control" type="number" name="quantity" required>
                                        <input class="form-control" type="text" name="notes" placeholder="Obs." style="margin-top:.35rem;">
                                        <button class="btn btn-ghost" type="submit" style="margin-top:.35rem;">Ajustar</button>
                                    </form>
                                </div>
                            </details>
                        @endif
                        @unless($product->hasLinkedSales())
                            <form method="POST" action="{{ route('commissions.products.destroy', $product) }}" style="display:inline;"
                                  onsubmit="return confirm('Excluir este produto?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost" type="submit">Excluir</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Nenhum produto cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 style="margin:0 0 .75rem;font-size:1.05rem;">Últimas movimentações</h2>
        <table class="table" style="width:100%;">
            <thead>
            <tr>
                <th>Quando</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th>Qtd</th>
                <th>Responsável</th>
                <th>Obs.</th>
            </tr>
            </thead>
            <tbody>
            @forelse($recentMovements as $mov)
                <tr>
                    <td>{{ optional($mov->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $mov->product?->name }}</td>
                    <td>{{ $mov->type->label() }}</td>
                    <td>{{ $mov->quantity > 0 ? '+'.$mov->quantity : $mov->quantity }}</td>
                    <td>{{ $mov->user?->name }}</td>
                    <td>{{ $mov->notes ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Sem movimentações.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
