@extends('layouts.operational')

@section('title', 'Produtos')

@section('page')
    <div class="products-admin-header" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start;margin-bottom:1rem;">
        <div style="min-width:0;flex:1;">
            <h1 class="page-title" style="margin:0;">Produtos</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Configurações → Produtos — catálogo e materiais de apresentação para o campo.</p>
        </div>
        <div class="actions" style="display:flex;gap:.5rem;flex-wrap:wrap;width:100%;max-width:100%;">
            <a class="btn btn-ghost" href="{{ route('operations.settings') }}" style="min-height:44px;">← Configurações</a>
            <a class="btn btn-primary" id="btn-new-product" href="{{ route('commissions.products.create') }}" style="min-height:44px;flex:1 1 12rem;">+ Novo produto</a>
        </div>
    </div>

    @if($products->isEmpty())
        <div class="card" style="text-align:center;padding:2rem 1.25rem;margin-bottom:1.25rem;">
            <p style="margin:0 0 1rem;color:var(--muted);">Nenhum produto cadastrado ainda.</p>
            <a class="btn btn-primary" href="{{ route('commissions.products.create') }}" style="min-height:48px;display:inline-flex;">+ Novo produto</a>
        </div>
    @endif

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
        <a class="btn btn-ghost" href="{{ route('commissions.index') }}">Ver comissões</a>
    </form>

    <div class="card" style="overflow-x:auto;margin-bottom:1.25rem;">
        <table class="table client-data-table--responsive" style="width:100%;">
            <thead>
            <tr>
                <th>Produto</th>
                <th>Categoria</th>
                <th>Preço (R$)</th>
                <th>Estoque</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td data-label="Produto">
                        <div style="display:flex; gap:.65rem; align-items:center;">
                            @if($product->imageUrl())
                                <img src="{{ $product->imageUrl() }}" alt="" style="width:40px;height:40px;border-radius:.55rem;object-fit:cover;border:1px solid var(--border);">
                            @endif
                            <div>
                                <strong>{{ $product->name }}</strong>
                                @if($product->benefitList() !== [])
                                    <div class="header-meta">{{ $product->benefitList()[0] }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="Categoria">{{ $product->category ?: '—' }}</td>
                    <td data-label="Preço">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</td>
                    <td data-label="Estoque">{{ $product->stock_control ? $product->stock_quantity : 'Sem controle' }}</td>
                    <td data-label="Status">{{ $product->status === 'active' ? 'Ativo' : 'Inativo' }}</td>
                    <td data-label="Ações" style="white-space:nowrap;">
                        <div class="actions" style="flex-wrap:wrap;">
                            <a class="btn btn-ghost" href="{{ route('commissions.products.edit', $product) }}" style="min-height:44px;">Editar</a>
                            <form method="POST" action="{{ route('commissions.products.toggle-status', $product) }}" style="display:inline;">
                                @csrf
                                <button class="btn btn-ghost" type="submit" style="min-height:44px;">
                                    {{ $product->isActive() ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                            <a class="btn btn-ghost" href="{{ route('sales-app.products.present', ['product' => $product->id]) }}" style="min-height:44px;">Ver apresentação</a>
                            @if($product->stock_control)
                                <details style="display:inline-block;">
                                    <summary class="btn btn-ghost" style="cursor:pointer;min-height:44px;">Estoque</summary>
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
                                    <button class="btn btn-ghost" type="submit" style="min-height:44px;">Excluir</button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhum produto cadastrado.</td></tr>
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
