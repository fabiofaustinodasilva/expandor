@extends('layouts.platform')

@section('title', 'Nova empresa')

@section('content')
    @php
        $plansPayload = $plans->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->price,
            'max_sellers' => $p->max_sellers,
        ])->values();
    @endphp

    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Nova empresa cliente</h1>
        <div class="header-meta">Contratação SaaS: empresa, plano, fidelidade, vencimento e primeira fatura</div>
    </div>

    <form method="POST" action="{{ route('platform.companies.store') }}" id="commercial-onboarding-form" class="company-create-form">
        @csrf

        <div class="card" style="margin-bottom:1rem;">
            <h2 style="margin-top:0;">1. Dados da empresa</h2>
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="company_name">Nome fantasia *</label>
                    <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required>
                    @error('company_name')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="legal_name">Razão social *</label>
                    <input class="form-control" type="text" name="legal_name" id="legal_name" value="{{ old('legal_name') }}" required>
                    @error('legal_name')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="document">CPF/CNPJ *</label>
                    <input class="form-control" type="text" name="document" id="document" value="{{ old('document') }}" required inputmode="numeric">
                    @error('document')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="company_phone">Telefone / WhatsApp *</label>
                    <input class="form-control" type="text" name="company_phone" id="company_phone" value="{{ old('company_phone') }}" required>
                    @error('company_phone')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="company_email">E-mail da empresa *</label>
                    <input class="form-control" type="email" name="company_email" id="company_email" value="{{ old('company_email') }}" required>
                    @error('company_email')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="company_city">Cidade *</label>
                    <input class="form-control" type="text" name="company_city" id="company_city" value="{{ old('company_city') }}" required>
                    @error('company_city')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="company_uf">UF *</label>
                    <input class="form-control" type="text" name="company_uf" id="company_uf" value="{{ old('company_uf') }}" required maxlength="2" style="text-transform:uppercase;">
                    @error('company_uf')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1rem;">
            <h2 style="margin-top:0;">2. Plano e contratação</h2>
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="plan_id">Plano *</label>
                    <select class="form-control" name="plan_id" id="plan_id" required>
                        <option value="">Selecione</option>
                        @foreach($plans as $plan)
                            <option
                                value="{{ $plan->id }}"
                                data-price="{{ (float) $plan->price }}"
                                data-name="{{ $plan->name }}"
                                data-sellers="{{ $plan->max_sellers ?? '' }}"
                                @selected((string) old('plan_id') === (string) $plan->id)
                            >
                                {{ $plan->name }}
                                @if((float) $plan->price > 0)
                                    — R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                                @else
                                    — Gratuito
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="header-meta" id="plan-hint" style="margin-top:.35rem;"></div>
                    @error('plan_id')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="contract_started_at">Data de início do contrato *</label>
                    <input class="form-control" type="date" name="contract_started_at" id="contract_started_at" value="{{ old('contract_started_at', now()->toDateString()) }}" required>
                    @error('contract_started_at')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="billing_day">Dia de vencimento *</label>
                    <select class="form-control" name="billing_day" id="billing_day" required>
                        @foreach([5,10,15,20,25,28] as $day)
                            <option value="{{ $day }}" @selected((string) old('billing_day', '10') === (string) $day)>Dia {{ $day }}</option>
                        @endforeach
                    </select>
                    @error('billing_day')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="fidelity_mode">Fidelidade *</label>
                    <select class="form-control" name="fidelity_mode" id="fidelity_mode" required>
                        <option value="none" @selected(old('fidelity_mode') === 'none')>Sem fidelidade</option>
                        <option value="3" @selected(old('fidelity_mode') === '3')>3 meses</option>
                        <option value="6" @selected(old('fidelity_mode', '6') === '6')>6 meses</option>
                        <option value="12" @selected(old('fidelity_mode') === '12')>12 meses</option>
                        <option value="custom" @selected(old('fidelity_mode') === 'custom')>Personalizada</option>
                    </select>
                    @error('fidelity_mode')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" id="fidelity-custom-wrap" style="{{ old('fidelity_mode') === 'custom' ? '' : 'display:none;' }}">
                    <label for="fidelity_custom_months">Quantidade de meses *</label>
                    <input class="form-control" type="number" min="1" max="60" name="fidelity_custom_months" id="fidelity_custom_months" value="{{ old('fidelity_custom_months') }}">
                    @error('fidelity_custom_months')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,.08);">
                <label style="display:flex; gap:.5rem; align-items:center;">
                    <input type="checkbox" name="has_commercial_exception" id="has_commercial_exception" value="1" @checked(old('has_commercial_exception'))>
                    Aplicar condição comercial especial
                </label>
                <div id="exception-fields" style="{{ old('has_commercial_exception') ? '' : 'display:none;' }}; margin-top:1rem;" class="grid grid-2">
                    <div class="form-group">
                        <label for="negotiated_amount">Mensalidade negociada *</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="negotiated_amount" id="negotiated_amount" value="{{ old('negotiated_amount') }}">
                        @error('negotiated_amount')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label style="display:flex; gap:.5rem; align-items:center; margin-bottom:.35rem;">
                            <input type="checkbox" name="first_due_at_override" id="first_due_at_override" value="1" @checked(old('first_due_at_override'))>
                            Personalizar primeiro vencimento
                        </label>
                        <input class="form-control" type="date" name="first_due_at" id="first_due_at" value="{{ old('first_due_at') }}">
                        @error('first_due_at')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="grid-column:1 / -1;">
                        <label for="commercial_exception_reason">Motivo da exceção *</label>
                        <textarea class="form-control" name="commercial_exception_reason" id="commercial_exception_reason" rows="3">{{ old('commercial_exception_reason') }}</textarea>
                        @error('commercial_exception_reason')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1rem;">
            <h2 style="margin-top:0;">3. Administrador da empresa</h2>
            <p class="header-meta" style="margin-top:0;">Será o primeiro usuário com acesso administrativo à conta.</p>
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="admin_name">Nome *</label>
                    <input class="form-control" type="text" name="admin_name" id="admin_name" value="{{ old('admin_name') }}" required>
                    @error('admin_name')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="admin_email">E-mail *</label>
                    <input class="form-control" type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" required>
                    @error('admin_email')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="admin_phone">Telefone / WhatsApp *</label>
                    <input class="form-control" type="text" name="admin_phone" id="admin_phone" value="{{ old('admin_phone') }}" required>
                    @error('admin_phone')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="admin_password">Senha *</label>
                    <input class="form-control" type="password" name="admin_password" id="admin_password" required minlength="8">
                    @error('admin_password')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="admin_password_confirmation">Confirmar senha *</label>
                    <input class="form-control" type="password" name="admin_password_confirmation" id="admin_password_confirmation" required minlength="8">
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1rem;" id="contract-summary">
            <h2 style="margin-top:0;">4. Resumo da contratação</h2>
            <div id="exception-badge" class="header-meta" style="display:none; color:#fbbf24; margin-bottom:.5rem;">Condição comercial especial</div>
            <dl class="summary-dl">
                <div><dt>Empresa</dt><dd id="sum-company">—</dd></div>
                <div><dt>Plano</dt><dd id="sum-plan">—</dd></div>
                <div><dt>Mensalidade</dt><dd id="sum-amount">—</dd></div>
                <div><dt>Vencimento</dt><dd id="sum-billing-day">—</dd></div>
                <div><dt>Início do contrato</dt><dd id="sum-start">—</dd></div>
                <div><dt>Fidelidade</dt><dd id="sum-fidelity">—</dd></div>
                <div><dt>Fim da fidelidade</dt><dd id="sum-fidelity-end">—</dd></div>
                <div><dt>Primeiro vencimento</dt><dd id="sum-first-due">—</dd></div>
            </dl>
        </div>

        <div class="actions company-create-actions">
            <a class="btn btn-ghost" href="{{ route('platform.companies.index') }}">Cancelar</a>
            <button class="btn btn-primary company-create-cta" type="submit">Criar empresa e ativar assinatura</button>
        </div>
    </form>

    <style>
        .company-create-form .form-group { margin-bottom: .85rem; }
        .summary-dl { margin:0; display:grid; gap:.55rem; }
        .summary-dl > div { display:grid; grid-template-columns: 12rem 1fr; gap:.75rem; }
        .summary-dl dt { margin:0; opacity:.75; }
        .summary-dl dd { margin:0; font-weight:600; }
        .company-create-actions { display:flex; gap:.75rem; flex-wrap:wrap; }
        .company-create-cta { min-height:44px; padding:.7rem 1.1rem; }
        @media (max-width: 720px) {
            .grid.grid-2 { grid-template-columns: 1fr !important; }
            .summary-dl > div { grid-template-columns: 1fr; gap:.1rem; }
            .company-create-actions { flex-direction: column; }
            .company-create-cta, .company-create-actions .btn { width:100%; }
        }
    </style>

    <script>
        (function () {
            var plans = @json($plansPayload);
            function pad(n){ return String(n).padStart(2,'0'); }
            function fmtDate(d){ if(!d) return '—'; return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear(); }
            function money(v){ return 'R$ ' + Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2, maximumFractionDigits:2}); }
            function firstDue(startStr, day){
                if(!startStr) return null;
                var start = new Date(startStr+'T00:00:00');
                var y = start.getFullYear(), m = start.getMonth();
                var dim = new Date(y, m+1, 0).getDate();
                var cand = new Date(y, m, Math.min(day, dim));
                if (cand < start) {
                    var nm = m+1; var ny = y; if(nm>11){nm=0;ny++;}
                    var dim2 = new Date(ny, nm+1, 0).getDate();
                    cand = new Date(ny, nm, Math.min(day, dim2));
                }
                return cand;
            }
            function addMonths(d, months){
                var x = new Date(d.getTime());
                var day = x.getDate();
                x.setMonth(x.getMonth()+months);
                if (x.getDate() < day) x.setDate(0);
                return x;
            }
            function refresh(){
                var planSel = document.getElementById('plan_id');
                var opt = planSel.options[planSel.selectedIndex];
                var plan = plans.find(function(p){ return String(p.id) === String(planSel.value); });
                var special = document.getElementById('has_commercial_exception').checked;
                var amount = special ? Number(document.getElementById('negotiated_amount').value || 0) : Number(plan?.price || 0);
                var start = document.getElementById('contract_started_at').value;
                var day = Number(document.getElementById('billing_day').value || 10);
                var due = (special && document.getElementById('first_due_at_override').checked && document.getElementById('first_due_at').value)
                    ? new Date(document.getElementById('first_due_at').value+'T00:00:00')
                    : firstDue(start, day);
                var mode = document.getElementById('fidelity_mode').value;
                var months = mode === 'none' ? null : (mode === 'custom' ? Number(document.getElementById('fidelity_custom_months').value||0) : Number(mode));
                var end = (start && months) ? addMonths(new Date(start+'T00:00:00'), months) : null;
                document.getElementById('sum-company').textContent = document.getElementById('company_name').value || '—';
                document.getElementById('sum-plan').textContent = plan?.name || '—';
                document.getElementById('sum-amount').textContent = money(amount);
                document.getElementById('sum-billing-day').textContent = 'Dia ' + day;
                document.getElementById('sum-start').textContent = start ? fmtDate(new Date(start+'T00:00:00')) : '—';
                document.getElementById('sum-fidelity').textContent = months ? (months + ' meses') : 'Sem fidelidade';
                document.getElementById('sum-fidelity-end').textContent = end ? fmtDate(end) : '—';
                document.getElementById('sum-first-due').textContent = due ? fmtDate(due) : '—';
                document.getElementById('exception-badge').style.display = special ? 'block' : 'none';
                var sellers = opt?.dataset?.sellers;
                document.getElementById('plan-hint').textContent = plan
                    ? ('Mensalidade padrão: ' + money(plan.price) + (sellers ? ' · até ' + sellers + ' vendedores' : (sellers === '0' || sellers === '' ? '' : '')))
                    : '';
                if (plan && (plan.max_sellers === null || plan.max_sellers === undefined)) {
                    // keep hint from option dataset
                }
            }
            function toggle(){
                document.getElementById('fidelity-custom-wrap').style.display = document.getElementById('fidelity_mode').value === 'custom' ? '' : 'none';
                document.getElementById('exception-fields').style.display = document.getElementById('has_commercial_exception').checked ? '' : 'none';
                refresh();
            }
            ['company_name','plan_id','contract_started_at','billing_day','fidelity_mode','fidelity_custom_months','has_commercial_exception','negotiated_amount','first_due_at','first_due_at_override']
                .forEach(function(id){
                    var el = document.getElementById(id);
                    if (!el) return;
                    el.addEventListener('change', toggle);
                    el.addEventListener('input', refresh);
                });
            toggle();
        })();
    </script>
@endsection
