@extends('layouts.operational')

@section('title', 'Mais opções')

@section('page')
@php
    $moreUser = auth()->user();
    $moreSections = $moreUser ? \App\Support\ClientArea\ClientNav::sections($moreUser) : [];
    $isSellerMais = $moreUser && \App\Support\ClientArea\ClientNav::isFieldSeller($moreUser);
@endphp

<x-client.page-header
    title="Mais opções"
    :description="$isSellerMais ? 'Atalhos úteis do dia a dia — perfil, academia e demais funções do vendedor.' : 'Telas administrativas e avançadas — fora do fluxo diário.'"
>
    <x-client.secondary-button :href="route('profile.edit')">Meu perfil</x-client.secondary-button>
</x-client.page-header>

@if(count($moreSections) > 0)
    <div class="grid op-wide-grid" data-op-more-grid="1">
        @foreach($moreSections as $section)
            <x-client.section-card :title="$section['label']">
                <x-client.quick-actions>
                    @foreach($section['items'] as $item)
                        <x-client.secondary-button :href="route($item['route'], $item['params'])">{{ $item['label'] }}</x-client.secondary-button>
                    @endforeach
                </x-client.quick-actions>
            </x-client.section-card>
        @endforeach
    </div>
@else
    <x-client.empty-state
        title="Nenhuma opção adicional disponível"
        description="Seu perfil de acesso atual não libera telas avançadas."
        icon="lock"
    />
@endif
@endsection
