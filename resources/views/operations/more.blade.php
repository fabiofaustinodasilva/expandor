@extends('layouts.operational')

@section('title', 'Mais opções')

@section('page')
    <h1 class="page-title">Mais opções</h1>
    <p class="header-meta" style="margin-bottom:1.25rem;">Telas administrativas e avançadas — fora do fluxo diário.</p>

    @php $groups = collect($links)->groupBy('group'); @endphp
    @forelse($groups as $group => $items)
        <div class="card" style="margin-bottom:1rem;">
            <div class="header-meta" style="margin-bottom:.65rem;">{{ $group }}</div>
            <div class="actions">
                @foreach($items as $link)
                    <a class="btn btn-ghost" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card">Nenhuma opção adicional disponível para o seu perfil.</div>
    @endforelse
@endsection
