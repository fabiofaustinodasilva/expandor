@extends('layouts.operational')

@section('title', 'Agenda')

@section('page')
@php
    $authUser = auth()->user();
@endphp
    <x-client.page-header
        title="Agenda"
        :description="$teamView ? 'Retornos da equipe — visão operacional completa.' : 'Seus retornos agendados — acompanhe e conclua sem sair da tela.'"
    >
        <x-client.secondary-button :href="route('map.index')">Abrir mapa</x-client.secondary-button>
    </x-client.page-header>

    <div class="flex flex-wrap gap-2 mb-4" style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem;">
        <a class="btn {{ empty($dayFilter) ? 'btn-primary' : 'btn-ghost' }}" href="{{ route('follow-ups.index') }}">Todos</a>
        <a class="btn {{ ($dayFilter ?? null) === 'today' ? 'btn-primary' : 'btn-ghost' }}" href="{{ route('follow-ups.index', ['day' => 'today']) }}">Hoje</a>
    </div>

    @if($followUps->isEmpty())
        <div class="card">
            @if(($dayFilter ?? null) === 'today')
                <x-client.empty-state
                    title="Nenhum retorno hoje"
                    description="Não há retornos pendentes agendados para hoje."
                    action-href="{{ route('follow-ups.index') }}"
                    action-label="Ver todos"
                    icon="calendar-clock"
                />
            @else
                <x-client.empty-state
                    title="Nenhum retorno na agenda"
                    description="Quando o resultado for Retornar, o ponto aparece aqui na data combinada."
                    action-href="{{ route('map.index') }}"
                    action-label="Ir ao mapa"
                    icon="calendar-clock"
                />
            @endif
        </div>
    @else
        <div class="agenda-list" style="display:grid; gap:0.85rem;">
            @foreach($followUps as $followUp)
                @php
                    $property = $followUp->visit?->property;
                    $address = $property?->address;
                    $resident = $property?->residents?->sortByDesc('is_primary_contact')->first()
                        ?? $property?->residents?->first();
                    $phoneDigits = preg_replace('/\D+/', '', (string) ($resident?->phone ?? ''));
                    if ($phoneDigits !== '' && ! str_starts_with($phoneDigits, '55')) {
                        $phoneDigits = '55'.$phoneDigits;
                    }
                    $clientName = $resident?->name ?: ($address?->label() ?: 'Cliente');
                    $addressLabel = $address?->label() ?: 'Endereço não informado';
                    $scheduledLabel = $followUp->scheduleLabel();
                    $scheduleTimeHint = $followUp->scheduleTimeHint();
                    $isOverdue = $followUp->isScheduleOverdue();
                    $lat = $property?->latitude ?? $followUp->visit?->latitude;
                    $lng = $property?->longitude ?? $followUp->visit?->longitude;
                    $waText = rawurlencode(
                        "Olá".($resident?->name ? ' '.$resident->name : '')."! Sou {$authUser->name} da {$companyName}. Estou confirmando nosso retorno agendado para {$scheduledLabel}."
                    );
                    $waHref = $phoneDigits !== ''
                        ? "https://wa.me/{$phoneDigits}?text={$waText}"
                        : null;
                    $routeHref = ($lat !== null && $lng !== null)
                        ? "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}"
                        : null;
                    $detailsHref = $property
                        ? route('map.index', ['property' => $property->id])
                        : ($followUp->visit_id ? route('visits.show', $followUp->visit_id) : route('map.index'));
                @endphp
                <article class="card agenda-card" style="padding:1rem 1.1rem;">
                    <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:flex-start;">
                        <div style="min-width:0; flex:1;">
                            <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; margin-bottom:.35rem;">
                                <span class="badge" style="{{ $isOverdue ? 'background:#7f1d1d;color:#fecaca;' : 'background:#0c4a6e;color:#bae6fd;' }}">
                                    {{ $isOverdue ? 'Atrasado · ' : '' }}{{ $scheduledLabel }}
                                </span>
                                @if($scheduleTimeHint)
                                    <span class="badge" style="background:#1e293b;color:#94a3b8;">{{ $scheduleTimeHint }}</span>
                                @endif
                                @if($teamView)
                                    <span class="badge">{{ $followUp->user?->name ?: '—' }}</span>
                                @endif
                            </div>
                            <h2 style="margin:0; font-size:1.05rem; font-weight:700;">{{ $clientName }}</h2>
                            <p class="header-meta" style="margin:.25rem 0 0;">{{ $addressLabel }}</p>
                            <p class="header-meta" style="margin:.2rem 0 0;">
                                <span class="badge" style="background:#1e293b;color:#94a3b8;">Pendente</span>
                                {{ $followUp->visit?->campaign?->name ?: 'Sem campanha' }}
                                @if($followUp->visit?->plan)
                                    · {{ $followUp->visit->plan }}
                                @endif
                                @if($followUp->notes)
                                    · {{ \Illuminate\Support\Str::limit($followUp->notes, 80) }}
                                @endif
                            </p>
                        </div>
                        <div class="actions" style="justify-content:flex-end;">
                            @if($waHref)
                                <a class="btn btn-ghost" href="{{ $waHref }}" target="_blank" rel="noopener" title="WhatsApp">WhatsApp</a>
                            @else
                                <button class="btn btn-ghost" type="button" disabled style="opacity:.45;" title="Sem telefone">WhatsApp</button>
                            @endif
                            @if($routeHref)
                                <a class="btn btn-ghost" href="{{ $routeHref }}" target="_blank" rel="noopener" title="Rota no Google Maps">Rota</a>
                            @else
                                <button class="btn btn-ghost" type="button" disabled style="opacity:.45;">Rota</button>
                            @endif
                            <a class="btn btn-ghost" href="{{ $detailsHref }}">Detalhes</a>
                            @can('complete', $followUp)
                                <button
                                    class="btn btn-primary agenda-complete-btn"
                                    type="button"
                                    data-follow-up-id="{{ $followUp->id }}"
                                    data-client="{{ e($clientName) }}"
                                    data-complete-url="{{ route('follow-ups.complete', $followUp) }}"
                                >Concluir</button>
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <x-client.pagination-bar :paginator="$followUps" />
    @endif

    {{-- Modal resultado (padrão Sprint 4.1) --}}
    <div id="agenda-outcome-modal" class="agenda-modal" hidden aria-hidden="true">
        <div class="agenda-modal-backdrop" data-agenda-close></div>
        <div class="agenda-modal-panel card" role="dialog" aria-modal="true" aria-labelledby="agenda-outcome-title">
            <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem;">
                <div>
                    <h2 id="agenda-outcome-title" class="page-title" style="margin:0; font-size:1.15rem;">Resultado do retorno</h2>
                    <p id="agenda-outcome-client" class="header-meta" style="margin:.3rem 0 0;"></p>
                </div>
                <button type="button" class="btn-icon" data-agenda-close aria-label="Fechar">
                    <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            </div>

            <form id="agenda-outcome-form" method="POST" action="#">
                @csrf
                <input type="hidden" name="status" id="agenda-status" value="">

                <div class="grid" style="gap:.55rem; margin-bottom:1rem;" id="agenda-outcome-group">
                    @foreach($outcomeOptions as $value => $label)
                        <button type="button" class="agenda-outcome-btn" data-status="{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>

                <div id="agenda-plan-block" hidden>
                    @include('partials.sale-finalize-fields', [
                        'prefix' => 'agenda',
                        'requiredChecklist' => $saleRequiredChecklist ?? [],
                        'sellableProducts' => $sellableProducts ?? [],
                    ])
                </div>
                <div class="form-group">
                    <label for="agenda-notes">Anotação da visita</label>
                    <textarea class="form-control" id="agenda-notes" name="notes" rows="2" placeholder="Opcional"></textarea>
                </div>

                <div id="agenda-return-block" hidden style="margin-top:.75rem; padding-top:.75rem; border-top:1px solid #1f2937;">
                    <div style="font-weight:700; margin-bottom:.65rem;">Novo agendamento</div>
                    <div class="grid grid-2" style="margin-bottom:.65rem;">
                        <div class="form-group" style="margin:0;">
                            <label for="agenda-follow-date">Data do retorno *</label>
                            <input class="form-control" type="date" id="agenda-follow-date" min="{{ \App\Support\AppTime::today() }}" required>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label for="agenda-follow-time">Horário <span style="font-weight:400; color:#64748b;">(opcional)</span></label>
                            <input class="form-control" type="time" id="agenda-follow-time">
                        </div>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="agenda-follow-notes">Observação do retorno</label>
                        <textarea class="form-control" id="agenda-follow-notes" name="follow_up_notes" rows="2" placeholder="Ex.: Levar tabela promocional"></textarea>
                    </div>
                    <input type="hidden" name="follow_up_at" id="agenda-follow-up-at" value="">
                </div>

                <div class="actions" style="margin-top:1.1rem;">
                    <button class="btn btn-primary" type="submit" id="agenda-save-btn" disabled>Salvar resultado</button>
                    <button class="btn btn-ghost" type="button" data-agenda-close>Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .agenda-outcome-btn {
            width: 100%; text-align: left; padding: .85rem 1rem; border-radius: .85rem;
            border: 1px solid #334155; background: #0f172a; color: #e2e8f0;
            font-weight: 700; font-size: .95rem; cursor: pointer;
        }
        .agenda-outcome-btn:hover { border-color: #38bdf8; }
        .agenda-outcome-btn.is-selected {
            border-color: #38bdf8; background: rgba(56,189,248,.12); box-shadow: 0 0 0 1px rgba(56,189,248,.35);
        }
        .agenda-modal {
            position: fixed; inset: 0; z-index: 70; display: flex; align-items: flex-end; justify-content: center;
            padding: 0;
        }
        .agenda-modal[hidden] { display: none !important; }
        .agenda-modal-backdrop {
            position: absolute; inset: 0; background: rgba(2,6,23,.72);
        }
        .agenda-modal-panel {
            position: relative; z-index: 1; width: min(520px, 100%);
            max-height: min(92dvh, 720px); overflow: auto;
            border-radius: 1.25rem 1.25rem 0 0; margin: 0;
            animation: agendaSlide .22s ease;
        }
        @media (min-width: 768px) {
            .agenda-modal { align-items: center; padding: 1.5rem; }
            .agenda-modal-panel { border-radius: 1.25rem; }
        }
        @keyframes agendaSlide {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: none; }
        }
        .form-group { margin-bottom: .85rem; }
        .form-group label { display: block; font-size: .8rem; color: #94a3b8; margin-bottom: .35rem; }
    </style>

    <script>
    (function () {
        const modal = document.getElementById('agenda-outcome-modal');
        const form = document.getElementById('agenda-outcome-form');
        const statusInput = document.getElementById('agenda-status');
        const clientEl = document.getElementById('agenda-outcome-client');
        const planBlock = document.getElementById('agenda-plan-block');
        const returnBlock = document.getElementById('agenda-return-block');
        const saveBtn = document.getElementById('agenda-save-btn');
        const followDate = document.getElementById('agenda-follow-date');
        const followTime = document.getElementById('agenda-follow-time');
        const followAt = document.getElementById('agenda-follow-up-at');
        const INSTALL = 'installation_requested';
        const RETURN = 'return_later';
        const saleRequired = @json($saleRequiredFields ?? []);
        const saleLabels = @json(\App\Domains\Sales\SaleFields\SaleFieldKeys::labels());
        const saleCustomerIds = {
            name: 'agenda-customer-name',
            phone: 'agenda-customer-phone',
            whatsapp: 'agenda-customer-whatsapp',
            document: 'agenda-customer-document',
            rg: 'agenda-customer-rg',
            email: 'agenda-customer-email',
            notes: 'agenda-sale-notes',
        };
        let agendaCart = [];

        function moneyBr(value) {
            return (Number(value) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function agendaProducts() {
            const root = document.getElementById('agenda-sale-finalize');
            try { return JSON.parse(root?.dataset.products || '[]'); } catch (e) { return []; }
        }

        function findAgendaProduct(id) {
            return agendaProducts().find((p) => Number(p.id) === Number(id)) || null;
        }

        function renderAgendaCart() {
            const wrap = document.getElementById('agenda-sale-cart-lines');
            const empty = document.getElementById('agenda-sale-cart-empty');
            const totalEl = document.getElementById('agenda-sale-cart-total');
            if (!wrap) return;
            wrap.innerHTML = '';
            agendaCart.forEach((line, idx) => {
                const p = findAgendaProduct(line.productId);
                const row = document.createElement('div');
                row.className = 'rounded-xl border border-slate-700 bg-slate-900/80 p-2 space-y-2';
                const select = document.createElement('select');
                select.className = 'form-control';
                select.innerHTML = '<option value="">Selecione</option>';
                agendaProducts().forEach((prod) => {
                    const opt = document.createElement('option');
                    opt.value = String(prod.id);
                    opt.textContent = `${prod.name} — ${moneyBr(prod.price)}`;
                    opt.disabled = !prod.available && Number(prod.id) !== Number(line.productId);
                    if (Number(prod.id) === Number(line.productId)) opt.selected = true;
                    select.appendChild(opt);
                });
                select.addEventListener('change', () => {
                    line.productId = Number(select.value) || 0;
                    renderAgendaCart();
                });
                const meta = document.createElement('div');
                meta.style.cssText = 'display:flex;gap:.5rem;align-items:center;justify-content:space-between;';
                meta.innerHTML = `<span style="font-size:.75rem;color:#94a3b8;">${p ? moneyBr((Number(p.price)||0)*(line.quantity||1)) : '—'}</span>`;
                const qty = document.createElement('input');
                qty.type = 'number'; qty.min = '1'; qty.value = String(line.quantity || 1);
                qty.className = 'form-control'; qty.style.width = '4.5rem';
                qty.addEventListener('change', () => {
                    let q = Math.max(1, parseInt(qty.value, 10) || 1);
                    const prod = findAgendaProduct(line.productId);
                    if (prod?.stock_control && q > Number(prod.stock_quantity)) {
                        alert('Estoque insuficiente.');
                        q = Math.max(1, Number(prod.stock_quantity) || 1);
                    }
                    line.quantity = q;
                    renderAgendaCart();
                });
                const del = document.createElement('button');
                del.type = 'button'; del.className = 'btn btn-ghost'; del.textContent = 'Excluir';
                del.addEventListener('click', () => {
                    agendaCart.splice(idx, 1);
                    renderAgendaCart();
                });
                meta.appendChild(qty);
                meta.appendChild(del);
                row.appendChild(select);
                row.appendChild(meta);
                wrap.appendChild(row);
            });
            if (empty) empty.hidden = agendaCart.length > 0;
            const total = agendaCart.reduce((s, l) => {
                const p = findAgendaProduct(l.productId);
                return s + ((Number(p?.price) || 0) * (Number(l.quantity) || 1));
            }, 0);
            if (totalEl) totalEl.textContent = moneyBr(total);
        }

        function syncFollowAt() {
            if (!followDate.value) {
                followAt.value = '';
                return;
            }
            followAt.value = followTime.value
                ? (followDate.value + 'T' + followTime.value)
                : followDate.value;
        }

        function setStatus(status) {
            statusInput.value = status;
            document.querySelectorAll('.agenda-outcome-btn').forEach((btn) => {
                btn.classList.toggle('is-selected', btn.dataset.status === status);
            });
            planBlock.hidden = status !== INSTALL;
            const finalize = document.getElementById('agenda-sale-finalize');
            if (finalize) finalize.classList.toggle('hidden', status !== INSTALL);
            returnBlock.hidden = status !== RETURN;
            saveBtn.disabled = !status;
            saveBtn.textContent = status === INSTALL ? 'Confirmar venda' : 'Salvar resultado';
            if (status === INSTALL) renderAgendaCart();
            if (status === RETURN) {
                if (!followDate.value) {
                    const d = new Date();
                    d.setDate(d.getDate() + 1);
                    followDate.value = d.toISOString().slice(0, 10);
                }
                syncFollowAt();
            } else {
                followAt.value = '';
            }
        }

        function openModal(btn) {
            form.action = btn.dataset.completeUrl;
            clientEl.textContent = btn.dataset.client || '';
            statusInput.value = '';
            form.reset();
            document.getElementById('agenda-notes').value = '';
            document.getElementById('agenda-follow-notes').value = '';
            followTime.value = '';
            followDate.value = '';
            followAt.value = '';
            agendaCart = [];
            form.querySelectorAll('input[name^="items["]').forEach((n) => n.remove());
            ['name', 'phone', 'whatsapp', 'document', 'rg', 'email', 'notes'].forEach((key) => {
                const el = document.getElementById(saleCustomerIds[key]);
                if (el) el.value = '';
            });
            renderAgendaCart();
            planBlock.hidden = true;
            document.getElementById('agenda-sale-finalize')?.classList.add('hidden');
            returnBlock.hidden = true;
            saveBtn.disabled = true;
            document.querySelectorAll('.agenda-outcome-btn').forEach((b) => b.classList.remove('is-selected'));
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('.agenda-complete-btn').forEach((btn) => {
            btn.addEventListener('click', () => openModal(btn));
        });
        document.querySelectorAll('[data-agenda-close]').forEach((el) => {
            el.addEventListener('click', closeModal);
        });
        document.querySelectorAll('.agenda-outcome-btn').forEach((btn) => {
            btn.addEventListener('click', () => setStatus(btn.dataset.status));
        });
        document.querySelectorAll('.sale-cart-add[data-prefix="agenda"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                agendaCart.push({ productId: 0, quantity: 1 });
                renderAgendaCart();
            });
        });
        followDate.addEventListener('change', syncFollowAt);
        followTime.addEventListener('change', syncFollowAt);

        form.addEventListener('submit', (e) => {
            const status = statusInput.value;
            if (!status) {
                e.preventDefault();
                return;
            }
            if (status === INSTALL) {
                for (const key of saleRequired) {
                    if (key === 'product') {
                        const items = agendaCart.filter((l) => Number(l.productId) > 0);
                        if (items.length === 0) {
                            e.preventDefault();
                            alert('Adicione ao menos um produto à venda.');
                            return;
                        }
                        continue;
                    }
                    const id = saleCustomerIds[key];
                    if (!id) continue;
                    const el = document.getElementById(id);
                    if (!el || !(el.value || '').trim()) {
                        e.preventDefault();
                        alert('Informe: ' + (saleLabels[key] || key) + '.');
                        return;
                    }
                }
                form.querySelectorAll('input[name^="items["]').forEach((n) => n.remove());
                agendaCart.filter((l) => Number(l.productId) > 0).forEach((line, i) => {
                    const pid = document.createElement('input');
                    pid.type = 'hidden';
                    pid.name = `items[${i}][product_id]`;
                    pid.value = String(line.productId);
                    form.appendChild(pid);
                    const qty = document.createElement('input');
                    qty.type = 'hidden';
                    qty.name = `items[${i}][quantity]`;
                    qty.value = String(Math.max(1, Number(line.quantity) || 1));
                    form.appendChild(qty);
                });
            }
            if (status === RETURN) {
                syncFollowAt();
                if (!followDate.value || !followAt.value) {
                    e.preventDefault();
                    alert('Informe a data do novo retorno.');
                    return;
                }
            }
        });
    })();
    </script>
@endsection
