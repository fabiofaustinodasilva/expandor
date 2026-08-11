@extends('layouts.operational')

@section('title', $dossier['name'])

@section('page')
    <x-client.page-header
        :title="$dossier['name']"
        :description="$dossier['seller'] ? 'Vendedor: '.$dossier['seller'] : null"
        eyebrow="Cliente"
    >
        <x-client.secondary-button :href="route('customers.index')">Voltar</x-client.secondary-button>
        @if(!empty($dossier['can_delete']))
            <form method="POST" action="{{ route('customers.destroy', $dossier['id']) }}" style="display:inline;"
                  onsubmit="return confirm('Excluir cliente?\nEsta ação só é permitida para clientes sem histórico.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Excluir</button>
            </form>
        @endif
    </x-client.page-header>
    @if(!empty($dossier['delete_blocked']))
        <p class="header-meta" style="margin:-0.35rem 0 1rem;">{{ $dossier['delete_blocked_message'] }}</p>
    @endif
    <p class="header-meta" style="margin:-0.5rem 0 1rem;">
        <span class="badge">{{ $dossier['situation'] }}</span>
    </p>

    <div class="actions" style="margin-bottom:1.15rem;">
        @if($dossier['actions']['whatsapp'])
            <a class="btn btn-ghost" href="{{ $dossier['actions']['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>
        @else
            <button class="btn btn-ghost" type="button" disabled style="opacity:.45;">WhatsApp</button>
        @endif
        @if($dossier['actions']['call'])
            <a class="btn btn-ghost" href="{{ $dossier['actions']['call'] }}">Ligar</a>
        @else
            <button class="btn btn-ghost" type="button" disabled style="opacity:.45;">Ligar</button>
        @endif
        <a class="btn btn-ghost" href="{{ $dossier['actions']['map'] }}">Abrir no mapa</a>
        <a class="btn btn-ghost" href="{{ $dossier['actions']['new_visit'] }}">Nova visita</a>
        <a class="btn btn-primary" href="{{ $dossier['actions']['new_sale'] }}">Nova venda</a>
        <a class="btn btn-ghost" href="{{ $dossier['actions']['schedule_return'] }}">Agendar retorno</a>
    </div>

    <div class="grid grid-2" style="align-items:start;">
        <section class="card">
            <h2 style="margin:0 0 .85rem; font-size:1rem;">Dados</h2>
            <dl class="customer-dl">
                <div><dt>Nome</dt><dd>{{ $dossier['name'] }}</dd></div>
                <div><dt>Telefone</dt><dd>{{ $dossier['phone'] ?: '—' }}</dd></div>
                <div><dt>WhatsApp</dt><dd>{{ $dossier['whatsapp'] ?: '—' }}</dd></div>
                <div><dt>CPF</dt><dd>{{ $dossier['document'] ?: '—' }}</dd></div>
                <div><dt>E-mail</dt><dd>{{ $dossier['email'] ?: '—' }}</dd></div>
                <div><dt>Endereço</dt><dd>{{ $dossier['address'] }}</dd></div>
                <div>
                    <dt>GPS</dt>
                    <dd>
                        @if($dossier['latitude'] !== null && $dossier['longitude'] !== null)
                            {{ number_format($dossier['latitude'], 6, '.', '') }}, {{ number_format($dossier['longitude'], 6, '.', '') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="card">
            <h2 style="margin:0 0 .85rem; font-size:1rem;">Resumo</h2>
            <dl class="customer-dl">
                <div><dt>Primeira visita</dt><dd>{{ $dossier['first_visit_at'] ?: '—' }}</dd></div>
                <div><dt>Última visita</dt><dd>{{ $dossier['last_visit_at'] ?: '—' }}</dd></div>
                <div><dt>Vendedor</dt><dd>{{ $dossier['seller'] }}</dd></div>
                <div><dt>Campanha</dt><dd>{{ $dossier['campaign'] ?: '—' }}</dd></div>
                <div><dt>Situação</dt><dd>{{ $dossier['situation'] }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin:0 0 .85rem; font-size:1rem;">Timeline</h2>
        @if(empty($dossier['timeline']))
            <p class="header-meta" style="margin:0;">Sem eventos ainda.</p>
        @else
            <ol class="customer-timeline">
                @foreach($dossier['timeline'] as $event)
                    <li>
                        <div class="tl-dot"></div>
                        <div>
                            <div class="tl-at">{{ $event['at'] }}</div>
                            <div class="tl-title">{{ $event['title'] }}</div>
                            @if(!empty($event['detail']))
                                <div class="tl-detail">{{ $event['detail'] }}</div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <div class="grid grid-2" style="margin-top:1rem; align-items:start;">
        <section class="card">
            <h2 style="margin:0 0 .85rem; font-size:1rem;">Produtos</h2>
            @if(empty($dossier['products']))
                <p class="header-meta" style="margin:0;">Nenhum produto registrado.</p>
            @else
                <div style="display:grid; gap:.55rem;">
                    @foreach($dossier['products'] as $product)
                        <div style="border:1px solid #1f2937; border-radius:.75rem; padding:.7rem .8rem;">
                            <div style="font-weight:700;">{{ $product['product_name'] }}</div>
                            <div class="header-meta" style="margin-top:.25rem;">
                                Qtd {{ $product['quantity'] }}
                                @if($product['line_total'] !== null)
                                    · R$ {{ number_format($product['line_total'], 2, ',', '.') }}
                                @endif
                                @if($product['date'])
                                    · {{ $product['date'] }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card">
            <h2 style="margin:0 0 .85rem; font-size:1rem;">Agenda</h2>
            @if(empty($dossier['next_follow_up']))
                <p class="header-meta" style="margin:0;">Sem retorno agendado.</p>
            @else
                <dl class="customer-dl">
                    <div><dt>Próximo retorno</dt><dd>{{ $dossier['next_follow_up']['at'] }}</dd></div>
                    @if(!empty($dossier['next_follow_up']['time_hint']))
                        <div><dt>Horário</dt><dd>{{ $dossier['next_follow_up']['time_hint'] }}</dd></div>
                    @endif
                    <div><dt>Observações</dt><dd>{{ $dossier['next_follow_up']['notes'] ?: '—' }}</dd></div>
                </dl>
            @endif
        </section>
    </div>

    @if(!empty($dossier['commissions']))
        <section class="card" style="margin-top:1rem;">
            <h2 style="margin:0 0 .85rem; font-size:1rem;">Comissões</h2>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dossier['commissions'] as $commission)
                            <tr>
                                <td>{{ $commission['product_name'] }}</td>
                                <td>R$ {{ number_format($commission['amount'], 2, ',', '.') }}</td>
                                <td>{{ $commission['status'] }}</td>
                                <td>{{ $commission['earned_at'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin:0 0 .85rem; font-size:1rem;">Histórico de visitas</h2>
        @if(empty($dossier['visits']))
            <p class="header-meta" style="margin:0;">Nenhuma visita registrada.</p>
        @else
            <div style="display:grid; gap:.55rem;">
                @foreach($dossier['visits'] as $visit)
                    <div style="border:1px solid #1f2937; border-radius:.75rem; padding:.75rem .85rem;">
                        <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
                            <span class="badge">{{ $visit['at'] }}</span>
                            <span class="badge">{{ $visit['result'] }}</span>
                        </div>
                        <div class="header-meta" style="margin-top:.35rem;">
                            {{ implode(' · ', array_filter([$visit['seller'], $visit['campaign']])) }}
                        </div>
                        @if($visit['notes'])
                            <p style="margin:.4rem 0 0; font-size:.9rem;">{{ $visit['notes'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @include('partials.ux.audit-meta', [
        'createdBy' => ($dossier['created_by'] ?? null) && ($dossier['created_by'] !== '—') ? $dossier['created_by'] : null,
        'updatedAt' => $dossier['updated_at'] ?? null,
        'responsible' => ($dossier['seller'] ?? null) && ($dossier['seller'] !== '—') ? $dossier['seller'] : null,
    ])

    <style>
        .customer-dl { margin:0; display:grid; gap:.65rem; }
        .customer-dl > div { display:grid; gap:.15rem; }
        .customer-dl dt { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#64748b; }
        .customer-dl dd { margin:0; color:#e2e8f0; font-weight:600; }
        .customer-timeline { list-style:none; margin:0; padding:0; display:grid; gap:0; }
        .customer-timeline li {
            display:grid; grid-template-columns:1.1rem 1fr; gap:.75rem;
            padding:.55rem 0; position:relative;
        }
        .customer-timeline li:not(:last-child)::before {
            content:''; position:absolute; left:.4rem; top:1.35rem; bottom:-.2rem;
            width:2px; background:#1e293b;
        }
        .tl-dot {
            width:.7rem; height:.7rem; border-radius:999px; margin-top:.35rem;
            background:#38bdf8; box-shadow:0 0 0 3px rgba(56,189,248,.18);
        }
        .tl-at { font-size:.75rem; color:#64748b; }
        .tl-title { font-weight:700; margin-top:.1rem; }
        .tl-detail { color:#94a3b8; font-size:.88rem; margin-top:.15rem; }
    </style>
@endsection
