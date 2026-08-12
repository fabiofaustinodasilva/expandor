@extends('layouts.operational')

@section('title', 'Clientes')

@section('page')
    <x-client.page-breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Operação'],
        ['label' => 'Clientes'],
    ]" />

    <x-client.page-header title="Clientes" description="CRM comercial — histórico, vendas e retornos em um só lugar.">
        <x-client.secondary-button :href="route('map.index')">Abrir mapa</x-client.secondary-button>
    </x-client.page-header>

    <x-client.crud-toolbar>
        <x-slot:search>
            <form method="GET" action="{{ route('customers.index') }}" style="display:flex; gap:.65rem; flex-wrap:wrap; width:100%;">
                <label class="sr-only" for="customer-q">Buscar clientes</label>
                <input id="customer-q" class="form-control" type="search" name="q" value="{{ $q }}"
                       placeholder="Nome, telefone, CPF, rua, bairro, cidade ou produto…"
                       style="flex:1; min-width:220px;">
                <button class="btn btn-primary client-btn" type="submit">Buscar</button>
                @if($q !== '')
                    <a class="btn btn-ghost client-btn" href="{{ route('customers.index') }}">Limpar</a>
                @endif
            </form>
        </x-slot:search>
    </x-client.crud-toolbar>

    @if($customers->isEmpty())
        <div class="card">
            <x-client.empty-state
                :title="$q !== '' ? 'Nenhum cliente encontrado' : 'Ainda não há clientes'"
                :description="$q !== '' ? 'Tente outro termo ou limpe a busca.' : 'Registre atendimentos no mapa — cada residência visitada aparece aqui.'"
                action-href="{{ route('map.index') }}"
                action-label="Ir para o mapa"
                icon="users"
            />
        </div>
    @else
        <div class="card client-data-table customer-list" data-op-customers-table="1" style="width:100%;">
            <table class="table client-data-table--responsive" data-customer-list="1">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Telefone</th>
                    <th>Local / Endereço</th>
                    <th>Status</th>
                    <th>Última visita</th>
                    <th>Última venda</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @foreach($customers as $card)
                    <tr>
                        <td data-label="Cliente">
                            <strong>{{ $card['name'] }}</strong>
                        </td>
                        <td data-label="Telefone">{{ $card['phone'] ?: $card['whatsapp'] ?: '—' }}</td>
                        <td data-label="Local / Endereço">
                            {{ $card['address'] }}
                            @if($card['neighborhood'] || $card['city'])
                                <div class="header-meta">{{ implode(' · ', array_filter([$card['neighborhood'], $card['city']])) }}</div>
                            @endif
                        </td>
                        <td data-label="Status"><x-client.status-badge>{{ $card['situation'] }}</x-client.status-badge></td>
                        <td data-label="Última visita">{{ $card['last_visit_at'] ?: '—' }}</td>
                        <td class="table-num" data-label="Última venda">{{ $card['last_sale'] }}</td>
                        <td data-label="Ações">
                            <div class="actions">
                                <a class="btn btn-ghost" href="{{ $card['show_url'] }}">Ver</a>
                                @if(!empty($card['can_delete']))
                                    <form method="POST" action="{{ route('customers.destroy', $card['id']) }}" style="display:inline;"
                                          onsubmit="return confirm('Excluir cliente?\nEsta ação só é permitida para clientes sem histórico.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Excluir</button>
                                    </form>
                                @elseif(!empty($card['delete_blocked']))
                                    <span class="header-meta" title="{{ \App\Domains\Customers\Services\CustomerDeletionService::BLOCKED_MESSAGE }}">Excluir indisponível</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-client.pagination-bar :paginator="$paginator" />
    @endif
@endsection
