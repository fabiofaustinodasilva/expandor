@extends('layouts.operational')

@section('title', 'Histórico de atendimentos')

@section('page')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1.25rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Histórico de atendimentos</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Seus atendimentos e resultados comerciais.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('map.index') }}">Abrir mapa</a>
    </div>

    @if($visits->isEmpty())
        <div class="card">
            <div class="empty-friendly">
                <div style="font-weight:700; font-size:1.05rem;">Você ainda não registrou atendimentos</div>
                <p>Abra o mapa, escolha a próxima casa e registre o resultado comercial.</p>
                <a class="btn btn-primary" href="{{ route('map.index') }}">Ir para o mapa</a>
            </div>
        </div>
    @else
        <div class="history-list" style="display:grid; gap:0.85rem;">
            @foreach($visits as $card)
                <article class="card history-card" style="padding:1rem 1.1rem;" data-visit-card="{{ $card['id'] }}">
                    <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:flex-start;">
                        <div style="min-width:0; flex:1;">
                            <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; margin-bottom:.4rem;">
                                <span class="badge" style="background:#0c4a6e;color:#bae6fd;">{{ $card['visited_at'] }}</span>
                                <span class="badge">{{ $card['result'] }}</span>
                            </div>
                            <h2 style="margin:0; font-size:1.05rem; font-weight:700;">{{ $card['client'] }}</h2>
                            @if($card['phone'])
                                <p class="header-meta" style="margin:.25rem 0 0;">{{ $card['phone'] }}</p>
                            @endif
                            @if($card['client'] !== $card['address'])
                                <p class="header-meta" style="margin:.2rem 0 0;">{{ $card['address'] }}</p>
                            @endif
                            <p class="header-meta" style="margin:.35rem 0 0;">
                                <span style="color:#cbd5e1;">{{ $card['campaign'] }}</span>
                            </p>
                            <p style="margin:.45rem 0 0; font-size:.9rem;">
                                <span class="header-meta">Próxima ação:</span>
                                <strong style="color:#e2e8f0; font-weight:600;">{{ $card['next_action'] }}</strong>
                            </p>
                        </div>
                        <div class="actions history-actions" style="justify-content:flex-end;">
                            <button
                                type="button"
                                class="btn btn-ghost history-detail-btn"
                                data-visit-id="{{ $card['id'] }}"
                                aria-haspopup="dialog"
                                aria-controls="history-detail-modal"
                            >👁 Detalhes</button>
                            @if($card['wa_href'])
                                <a class="btn btn-ghost" href="{{ $card['wa_href'] }}" target="_blank" rel="noopener">💬 WhatsApp</a>
                            @else
                                <button class="btn btn-ghost" type="button" disabled style="opacity:.45;" title="Sem telefone">💬 WhatsApp</button>
                            @endif
                            @if($card['route_href'])
                                <a class="btn btn-ghost" href="{{ $card['route_href'] }}" target="_blank" rel="noopener">🧭 Rota</a>
                            @else
                                <button class="btn btn-ghost" type="button" disabled style="opacity:.45;" title="Sem localização">🧭 Rota</button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1rem;">{{ $visits->links() }}</div>
    @endif

    {{-- Modal comercial (mesmo padrão Agenda) --}}
    <div id="history-detail-modal" class="history-modal" hidden aria-hidden="true">
        <div class="history-modal-backdrop" data-history-close></div>
        <div class="history-modal-panel card" role="dialog" aria-modal="true" aria-labelledby="history-detail-title">
            <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem;">
                <div>
                    <h2 id="history-detail-title" class="page-title" style="margin:0; font-size:1.15rem;">Detalhe do atendimento</h2>
                    <p id="history-detail-subtitle" class="header-meta" style="margin:.3rem 0 0;"></p>
                </div>
                <button type="button" class="btn btn-ghost" data-history-close aria-label="Fechar">✕</button>
            </div>

            <section class="history-section">
                <h3>Cliente</h3>
                <p id="hd-client" class="history-value">—</p>
            </section>

            <section class="history-section">
                <h3>Contato</h3>
                <p id="hd-phone" class="history-value">—</p>
            </section>

            <section class="history-section">
                <h3>Local</h3>
                <p id="hd-address" class="history-value">—</p>
            </section>

            <section class="history-section">
                <h3>Atendimento</h3>
                <dl class="history-detail-grid">
                    <div><dt>Data e horário</dt><dd id="hd-visited">—</dd></div>
                    <div><dt>Campanha</dt><dd id="hd-campaign">—</dd></div>
                    <div><dt>Vendedor responsável</dt><dd id="hd-seller">—</dd></div>
                    <div><dt>Resultado</dt><dd id="hd-result">—</dd></div>
                </dl>
            </section>

            <section class="history-section">
                <h3>Complementos</h3>
                <dl class="history-detail-grid">
                    <div><dt>Observação</dt><dd id="hd-notes">—</dd></div>
                    <div id="hd-plan-wrap"><dt>Plano contratado</dt><dd id="hd-plan">—</dd></div>
                </dl>
            </section>

            <section class="history-section" style="border-bottom:0; padding-bottom:0;">
                <h3>Próxima ação</h3>
                <p id="hd-next" class="history-value">—</p>
            </section>

            <div class="actions history-drawer-actions" style="margin-top:1.15rem;">
                <a id="hd-whatsapp" class="btn btn-ghost" href="#" target="_blank" rel="noopener" hidden>💬 WhatsApp</a>
                <a id="hd-route" class="btn btn-ghost" href="#" target="_blank" rel="noopener" hidden>🧭 Rota</a>
                <a id="hd-map" class="btn btn-ghost" href="#">📍 Ver no mapa</a>
                <a id="hd-new-return" class="btn btn-primary" href="#">🔄 Novo retorno</a>
            </div>
        </div>
    </div>

    <style>
        .history-actions .btn,
        .history-drawer-actions .btn { min-height: 44px; }
        .history-section {
            margin-bottom: .9rem;
            padding-bottom: .85rem;
            border-bottom: 1px solid #1f2937;
        }
        .history-section h3 {
            margin: 0 0 .4rem;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            font-weight: 700;
        }
        .history-value {
            margin: 0;
            font-size: 1rem;
            color: #e2e8f0;
            font-weight: 600;
            word-break: break-word;
        }
        .history-detail-grid {
            display: grid; gap: .65rem; margin: 0;
        }
        .history-detail-grid > div {
            display: grid; gap: .15rem;
        }
        .history-detail-grid dt {
            font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin: 0;
        }
        .history-detail-grid dd {
            margin: 0; font-size: .98rem; color: #e2e8f0; font-weight: 600; word-break: break-word;
        }
        .history-modal {
            position: fixed; inset: 0; z-index: 70;
            display: flex; align-items: flex-end; justify-content: center;
        }
        .history-modal[hidden] { display: none !important; }
        .history-modal-backdrop { position: absolute; inset: 0; background: rgba(2,6,23,.72); }
        .history-modal-panel {
            position: relative; z-index: 1; width: min(520px, 100%);
            max-height: min(92dvh, 760px); overflow: auto;
            border-radius: 1.25rem 1.25rem 0 0; margin: 0;
            animation: historySlide .22s ease;
        }
        @media (min-width: 768px) {
            .history-modal { align-items: center; padding: 1.5rem; }
            .history-modal-panel { border-radius: 1.25rem; }
        }
        @keyframes historySlide {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: none; }
        }
    </style>

    {{-- Payload fora de data-* (evita quebrar JSON com emoji / wa.me) --}}
    <script type="application/json" id="history-cards-data">@json($visits->getCollection()->keyBy('id')->all())</script>
    <script src="{{ asset('js/visit-history.js') }}?v=431" defer></script>
@endsection
