@extends('layouts.sales-app')

@section('title', 'Apresentar produtos')

@section('content')
    <h1 class="page-title">Apresentar produtos</h1>
    <p class="page-sub">Mostre planos e materiais ao cliente na hora da visita.</p>

    <a class="btn btn-primary"
       href="{{ route('sales-app.products.present') }}"
       style="min-height:52px; display:flex; align-items:center; justify-content:center; margin-bottom:1rem; font-size:1.05rem;">
        Abrir apresentação
    </a>

    <form method="GET" action="{{ route('sales-app.products.index') }}" style="margin-bottom:1rem;">
        <label class="sr-only" for="product-search">Buscar produto</label>
        <input id="product-search" type="search" name="q"
               value="{{ $search }}" placeholder="Buscar produto"
               style="min-height:44px; width:100%; border-radius:0.85rem; padding:0.75rem 1rem; background:var(--surface); border:1px solid var(--border); color:var(--text);">
    </form>

    @forelse($products as $product)
        <a href="{{ route('sales-app.products.present', ['product' => $product->id]) }}"
           style="padding:1rem; background:var(--surface); border:1px solid var(--border); border-radius:1rem; display:block; margin-bottom:0.75rem; text-decoration:none; color:inherit;">
            <div class="list-meta" style="margin-bottom:0.2rem;">{{ $product->categoryLabel() }}</div>
            <div class="list-title">{{ $product->name }}</div>
            @if($product->benefitList() !== [])
                <div class="list-meta" style="margin-top:0.25rem;">{{ $product->benefitList()[0] }}</div>
            @elseif($product->description)
                <div class="list-meta" style="margin-top:0.25rem;">{{ \Illuminate\Support\Str::limit($product->description, 80) }}</div>
            @endif
            <div style="margin-top:0.75rem; color:var(--accent); font-weight:700; font-size:0.9rem;">Ver apresentação</div>
        </a>
    @empty
        <div class="empty">Nenhum produto ativo disponível. Peça à empresa para cadastrar na área de Produtos.</div>
    @endforelse
@endsection
