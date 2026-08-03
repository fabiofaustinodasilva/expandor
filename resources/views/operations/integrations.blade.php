@extends('layouts.operational')

@section('title', 'Integrações')

@section('page')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Integrações</h1>
            <p class="header-meta">Configurações por empresa — em preparação.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('operations.settings') }}">Voltar</a>
    </div>

    <div class="grid grid-2">
        @foreach($integrations as $item)
            <div class="card">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start;">
                    <div>
                        <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.35rem;">
                            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>
                            <strong style="font-size:1.05rem;">{{ $item['name'] }}</strong>
                        </div>
                        <p class="header-meta" style="margin:0;">{{ $item['description'] }}</p>
                    </div>
                    <span class="badge">{{ $item['status'] }}</span>
                </div>
                <button type="button" class="btn btn-ghost" style="margin-top:1rem; width:100%;" disabled>Configurar em breve</button>
            </div>
        @endforeach
    </div>
@endsection
