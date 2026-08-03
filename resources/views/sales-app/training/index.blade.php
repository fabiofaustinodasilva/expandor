@extends('layouts.sales-app')

@section('title', 'Academia')

@section('content')
    <h1 class="page-title">Academia de Vendas</h1>
    <p class="page-sub">Capacitação da sua empresa</p>

    @forelse($categories as $category)
        <div class="card">
            <div class="list-title">{{ $category->name }}</div>
            @if($category->description)
                <div class="list-meta" style="margin-bottom:0.65rem;">{{ $category->description }}</div>
            @endif

            @forelse($category->contents as $content)
                @php $itemProgress = $progress->get($content->id); @endphp
                <a class="card card-link" style="margin-bottom:0.55rem;" href="{{ route('sales-app.training.show', $content) }}">
                    <div class="list-title" style="font-size:0.95rem;">{{ $content->title }}</div>
                    <div class="list-meta">
                        {{ $content->type?->label() }}
                        ·
                        @if($itemProgress?->completed_at)
                            <span class="badge badge-success">Concluído</span>
                        @elseif($itemProgress?->started_at)
                            <span class="badge badge-warning">Em andamento</span>
                        @else
                            <span class="badge">Não iniciado</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="list-meta">Nenhum conteúdo ativo nesta categoria.</div>
            @endforelse
        </div>
    @empty
        <div class="card empty">Nenhum treinamento disponível no momento.</div>
    @endforelse
@endsection
