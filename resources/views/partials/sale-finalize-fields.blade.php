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
@endphp
<div id="{{ $prefix }}-sale-finalize" class="hidden space-y-3 rounded-xl border border-emerald-700/50 bg-emerald-950/30 p-3"
     data-sale-cart-root="1"
     data-prefix="{{ $prefix }}"
     data-products='@json($productsJson)'
     data-require-product="{{ !empty($req['product']) ? '1' : '0' }}">
    <div class="text-sm font-semibold text-emerald-300 tracking-wide" data-sale-finalize-title="1">Confirmar venda</div>
    <p class="text-[11px] text-slate-400 -mt-1">Revise os produtos antes de finalizar.</p>

    <div class="space-y-2">
        <div class="text-[11px] uppercase tracking-wide text-slate-500">Dados do cliente</div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-name">Nome completo{!! $star('name') !!}</label>
            <input id="{{ $prefix }}-customer-name" name="customer_name" type="text" autocomplete="name"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="Nome completo">
        </div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-phone">Telefone{!! $star('phone') !!}</label>
            <input id="{{ $prefix }}-customer-phone" name="customer_phone" type="tel" inputmode="tel" autocomplete="tel"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="Telefone">
        </div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-whatsapp">WhatsApp{!! $star('whatsapp') !!}</label>
            <input id="{{ $prefix }}-customer-whatsapp" name="customer_whatsapp" type="tel" inputmode="tel"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="WhatsApp">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-document">CPF{!! $star('document') !!}</label>
                <input id="{{ $prefix }}-customer-document" name="customer_document" type="text"
                       class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                       placeholder="CPF">
            </div>
            <div>
                <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-rg">RG{!! $star('rg') !!}</label>
                <input id="{{ $prefix }}-customer-rg" name="customer_rg" type="text"
                       class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                       placeholder="RG">
            </div>
        </div>
        <div>
            <label class="text-xs text-slate-400" for="{{ $prefix }}-customer-email">E-mail{!! $star('email') !!}</label>
            <input id="{{ $prefix }}-customer-email" name="customer_email" type="email" autocomplete="email"
                   class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3"
                   placeholder="E-mail">
        </div>
    </div>

    <div class="space-y-2 pt-1">
        <div class="flex items-center justify-between gap-2">
            <div class="text-[11px] uppercase tracking-wide text-slate-500">Produtos{!! $star('product') !!}</div>
            <button type="button" class="sale-cart-add h-9 px-3 rounded-lg border border-emerald-600/60 text-emerald-300 text-xs font-semibold"
                    data-prefix="{{ $prefix }}">+ Adicionar produto</button>
        </div>
        <div id="{{ $prefix }}-sale-cart-lines" class="space-y-2"></div>
        <p id="{{ $prefix }}-sale-cart-empty" class="text-[11px] text-slate-500">Nenhum produto ainda. Toque em “+ Adicionar produto”.</p>
        <div class="rounded-xl bg-slate-950/80 border border-slate-700 px-3 py-2 flex items-center justify-between">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Total</span>
            <span id="{{ $prefix }}-sale-cart-total" class="text-lg font-bold text-emerald-300">R$ 0,00</span>
        </div>
    </div>

    <div>
        <label class="text-xs text-slate-400" for="{{ $prefix }}-sale-notes">Observações da venda{!! $star('notes') !!}</label>
        <textarea id="{{ $prefix }}-sale-notes" name="sale_notes" rows="2"
                  class="mt-1 w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2"
                  placeholder="Observações comerciais desta venda"></textarea>
    </div>
</div>
