@extends('layouts.operational')

@section('title', 'Integrações')

@section('page')
    <x-client.page-header
        title="Integrações"
        description="Conecte serviços externos liberados pelo seu plano."
    >
        <x-client.secondary-button :href="route('operations.settings')">Voltar</x-client.secondary-button>
    </x-client.page-header>

    @if(session('success'))
        <div class="card" style="margin-bottom:1rem; border-color:#86efac;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="card" style="margin-bottom:1rem; border-color:#fca5a5;">{{ session('error') }}</div>
    @endif

    <div class="card" style="margin-bottom:1.25rem;">
        <p class="header-meta" style="margin:0;">{{ $billingNotice }}</p>
    </div>

    @foreach($categories as $category)
        <section style="margin-bottom:1.75rem;">
            <h2 style="font-size:1rem; margin:0 0 .75rem; letter-spacing:.02em; text-transform:uppercase; opacity:.75;">{{ $category['title'] }}</h2>
            <div class="grid grid-2">
                @foreach($category['items'] as $item)
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start;">
                            <div>
                                <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.35rem;">
                                    <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>
                                    <strong style="font-size:1.05rem;">{{ $item['name'] }}</strong>
                                </div>
                                <p class="header-meta" style="margin:0;">{{ $item['description'] }}</p>
                                @if(!empty($item['secondary']))
                                    <p class="header-meta" style="margin:.5rem 0 0;">{{ $item['secondary'] }}</p>
                                @endif
                            </div>
                            <span class="badge">{{ $item['status_label'] }}</span>
                        </div>

                        @if($item['cta'] && $item['cta_route'])
                            @if($item['state'] === 'not_available')
                                <a class="btn btn-ghost" style="margin-top:1rem; width:100%; text-align:center;" href="{{ $item['cta_route'] }}">{{ $item['cta'] }}</a>
                            @elseif($canManage)
                                <a class="btn btn-primary" style="margin-top:1rem; width:100%; text-align:center;" href="{{ $item['cta_route'] }}">{{ $item['cta'] }}</a>
                            @else
                                <button type="button" class="btn btn-ghost" style="margin-top:1rem; width:100%;" disabled>Somente visualização</button>
                            @endif
                        @elseif($item['cta_disabled'])
                            <button type="button" class="btn btn-ghost" style="margin-top:1rem; width:100%;" disabled>
                                {{ $item['state'] === 'soon' ? 'Em breve' : 'Sem ação' }}
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
