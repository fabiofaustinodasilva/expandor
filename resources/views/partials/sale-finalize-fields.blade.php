{{--
  Finalizar venda — PDV externo (carrinho multi-produto).
  $prefix: visit|point|agenda
  $requiredChecklist: array<fieldKey, bool>
  $sellableProducts: list
--}}
@php
    $req = $requiredChecklist ?? [];
    $star = fn (string $key) => ! empty($req[$key]) ? ' <span class="text-rose-400">*</span>' : '';
    $productsJson = collect($sellableProducts ?? [])->values()->all();
    $dueDays = \App\Domains\Sales\Support\SaleDueDays::ALLOWED;
@endphp
<div id="{{ $prefix }}-sale-finalize" class="hidden space-y-3 rounded-xl border border-orange-700/40 bg-slate-950/40 p-3"
     data-sale-cart-root="1"
     data-prefix="{{ $prefix }}"
     data-products='@json($productsJson)'
     data-require-product="{{ !empty($req['product']) ? '1' : '0' }}">
    <div class="text-sm font-semibold text-emerald-300 tracking-wide" data-sale-finalize-title="1">CONFIRMAR VENDA</div>
    <p class="text-[11px] text-slate-400 -mt-1">Revise os dados antes de finalizar.</p>

    <section class="space-y-2 rounded-xl border border-sky-700/40 bg-sky-950/20 p-3">
        <div class="text-[11px] uppercase tracking-wide text-sky-300 font-semibold">Dados do cliente</div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-name">Nome Completo{!! $star('name') !!}</label>
            <input id="{{ $prefix }}-customer-name" name="customer_name" type="text" autocomplete="name"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="João da Silva">
        </div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-document">CPF{!! $star('document') !!}</label>
            <input id="{{ $prefix }}-customer-document" name="customer_document" type="text" inputmode="numeric"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="000.000.000-00" maxlength="14">
        </div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-birth">Data de Nascimento</label>
            <input id="{{ $prefix }}-customer-birth" name="customer_birth_date" type="text" inputmode="numeric"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="dd/mm/aaaa" maxlength="10">
        </div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-phone">Telefone / WhatsApp{!! $star('phone') !!}</label>
            <input id="{{ $prefix }}-customer-phone" name="customer_phone" type="tel" inputmode="tel" autocomplete="tel"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="(64) 99999-9999">
        </div>
        <input id="{{ $prefix }}-customer-whatsapp" name="customer_whatsapp" type="hidden">
        <input id="{{ $prefix }}-customer-rg" name="customer_rg" type="hidden">
        <input id="{{ $prefix }}-customer-email" name="customer_email" type="hidden">
    </section>

    <section class="space-y-2 rounded-xl border border-slate-600/50 bg-slate-900/40 p-3">
        <div class="text-[11px] uppercase tracking-wide text-slate-300 font-semibold">Endereço da instalação</div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-install-street">Rua / Avenida</label>
            <input id="{{ $prefix }}-install-street" name="install_street" type="text"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-xs text-slate-400" for="{{ $prefix }}-install-number">Número</label>
                <input id="{{ $prefix }}-install-number" name="install_number" type="text"
                       class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
            </div>
            <div>
                <label class="text-xs text-slate-400" for="{{ $prefix }}-install-neighborhood">Bairro</label>
                <input id="{{ $prefix }}-install-neighborhood" name="install_neighborhood" type="text"
                       class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
            </div>
        </div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-install-reference">Ponto de Referência</label>
            <input id="{{ $prefix }}-install-reference" name="install_reference" type="text"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="Próximo à praça">
        </div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-install-city">Cidade</label>
            <input id="{{ $prefix }}-install-city" name="install_city" type="text"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
        </div>
    </section>

    <section class="space-y-2 rounded-xl border border-orange-600/40 bg-orange-950/20 p-3">
        <div class="text-[11px] uppercase tracking-wide text-orange-300 font-semibold">Contratação</div>
        <div>
            <div class="text-xs text-slate-400 mb-2">Dia de vencimento</div>
            <input type="hidden" id="{{ $prefix }}-due-day" name="due_day" value="">
            <div class="grid grid-cols-3 gap-2" data-due-day-group="{{ $prefix }}">
                @foreach($dueDays as $day)
                    <button type="button" class="due-day-chip h-11 rounded-xl border border-slate-700 bg-slate-900 text-sm font-semibold"
                            data-prefix="{{ $prefix }}" data-day="{{ $day }}">{{ $day }}</button>
                @endforeach
            </div>
        </div>
        <div class="flex items-center justify-between gap-2 pt-1">
            <div class="text-[11px] uppercase tracking-wide text-orange-200/80">Produtos{!! $star('product') !!}</div>
            <button type="button" class="sale-cart-add h-9 px-3 rounded-lg border border-orange-600/60 text-orange-200 text-xs font-semibold"
                    data-prefix="{{ $prefix }}">+ Adicionar produto</button>
        </div>
        <div id="{{ $prefix }}-sale-cart-lines" class="space-y-2"></div>
        <p id="{{ $prefix }}-sale-cart-empty" class="text-[11px] text-slate-500">Nenhum produto ainda. Toque em “+ Adicionar produto”.</p>
        <div class="rounded-xl bg-slate-950/80 border border-slate-700 px-3 py-2 flex items-center justify-between">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Total</span>
            <span id="{{ $prefix }}-sale-cart-total" class="text-lg font-bold text-orange-200">R$ 0,00</span>
        </div>
    </section>

    <section class="space-y-2 rounded-xl border border-emerald-700/40 bg-emerald-950/20 p-3" data-sale-review="{{ $prefix }}">
        <div class="text-[11px] uppercase tracking-wide text-emerald-300 font-semibold">Revisão</div>
        <div id="{{ $prefix }}-sale-review" class="text-sm text-slate-200 space-y-1"></div>
        <p class="text-[11px] text-slate-500">Toque em Confirmar venda no rodapé para gravar. O WhatsApp só abre depois que a venda estiver salva.</p>
    </section>

    <div>
        <label class="text-xs text-slate-400" for="{{ $prefix }}-sale-notes">Observações da venda{!! $star('notes') !!}</label>
        <textarea id="{{ $prefix }}-sale-notes" name="sale_notes" rows="2"
                  class="mt-1 w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2"
                  placeholder="Observações comerciais desta venda"></textarea>
    </div>
</div>
