@extends('layouts.platform')

@section('title', 'Integrações')

@section('content')
    <h1 class="page-title">Integrações da plataforma</h1>
    <p class="header-meta">Catálogo global. Credenciais tenant nunca são exibidas aqui.</p>

    <div class="grid grid-2" style="gap:1rem;">
        @foreach($integrations as $item)
            <div class="card">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start;">
                    <div>
                        <strong style="font-size:1.1rem;">{{ $item['name'] }}</strong>
                        <div class="header-meta">{{ $item['category'] }} · {{ $item['management'] }}</div>
                    </div>
                    <span class="badge">{{ $item['platform_status'] }}</span>
                </div>
                <p style="margin:1rem 0 .5rem;">{{ $item['description'] }}</p>
                @if($item['connected_companies'] !== null)
                    <p class="header-meta" style="margin:0;">Empresas conectadas: <strong>{{ $item['connected_companies'] }}</strong></p>
                @endif
                @if(!empty($item['plans']))
                    <p class="header-meta" style="margin:.35rem 0 0;">Planos habilitados: {{ implode(', ', $item['plans']) }}</p>
                @endif
                <p class="header-meta" style="margin:.75rem 0 0;">{{ $item['notes'] }}</p>
                @if($item['manage_route'])
                    <a class="btn btn-ghost" style="margin-top:1rem;" href="{{ $item['manage_route'] }}">Abrir configuração</a>
                @endif
            </div>
        @endforeach
    </div>
@endsection
