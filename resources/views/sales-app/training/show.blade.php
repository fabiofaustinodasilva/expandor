@extends('layouts.sales-app')

@section('title', $content->title)

@section('content')
    <h1 class="page-title">{{ $content->title }}</h1>
    <p class="page-sub">
        {{ $content->category?->name }} · {{ $content->type?->label() }}
        @if($progress?->completed_at)
            · <span class="badge badge-success">Concluído</span>
        @endif
    </p>

    @if($content->description)
        <div class="card">
            <div class="list-meta">{{ $content->description }}</div>
        </div>
    @endif

    <div class="card">
        @if($content->type?->value === 'text')
            <div style="white-space:pre-wrap; line-height:1.5;">{{ $content->content }}</div>
        @elseif($content->url)
            <p class="list-meta" style="margin-bottom:0.75rem;">Acesse o material:</p>
            <a class="btn btn-primary" href="{{ $content->url }}" target="_blank" rel="noopener">Abrir {{ $content->type?->label() }}</a>
            @if($content->content)
                <div style="white-space:pre-wrap; line-height:1.5; margin-top:1rem;">{{ $content->content }}</div>
            @endif
        @else
            <div class="list-meta">Conteúdo indisponível.</div>
        @endif
    </div>

    <div class="btn-row">
        @if(! $progress?->completed_at)
            <form method="POST" action="{{ route('sales-app.training.complete', $content) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Marcar como concluído</button>
            </form>
        @endif
        <a class="btn btn-ghost" href="{{ route('sales-app.training.index') }}">Voltar à Academia</a>
    </div>
@endsection
