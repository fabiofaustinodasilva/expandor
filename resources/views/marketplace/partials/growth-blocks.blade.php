<style>
            .mkp-growth-section {
                padding: 4rem 0;
            }

            .mkp-form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 1rem;
            }

            .mkp-field label {
                display: block;
                font-size: 0.88rem;
                font-weight: 600;
                margin-bottom: 0.35rem;
                color: var(--mkp-text);
            }

            .mkp-field input,
            .mkp-field select,
            .mkp-field textarea {
                width: 100%;
                padding: 0.65rem 0.85rem;
                border-radius: 0.55rem;
                border: 1px solid var(--mkp-border);
                background: rgba(15, 23, 42, 0.55);
                color: var(--mkp-text);
                font: inherit;
            }

            .mkp-field input:focus,
            .mkp-field select:focus,
            .mkp-field textarea:focus {
                outline: none;
                border-color: color-mix(in srgb, var(--mkp-primary) 55%, transparent);
            }

            .mkp-alert-success {
                padding: 0.85rem 1rem;
                border-radius: 0.65rem;
                background: color-mix(in srgb, #22c55e 18%, transparent);
                border: 1px solid color-mix(in srgb, #22c55e 35%, transparent);
                color: #bbf7d0;
                margin-bottom: 1.25rem;
            }

            .mkp-roi-card {
                background: var(--mkp-surface);
                border: 1px solid var(--mkp-border);
                border-radius: var(--mkp-radius);
                padding: 2rem;
                max-width: 720px;
                margin: 0 auto;
            }

            .mkp-roi-result {
                margin-top: 1.25rem;
                padding: 1rem 1.15rem;
                border-radius: 0.65rem;
                background: color-mix(in srgb, var(--mkp-primary) 12%, transparent);
                border: 1px solid color-mix(in srgb, var(--mkp-primary) 30%, transparent);
            }

            .mkp-roi-result.is-hidden { display: none; }

            .mkp-roi-loss {
                font-size: 1.35rem;
                font-weight: 800;
                color: var(--mkp-button);
                margin: 0.35rem 0 0.75rem;
            }

            .mkp-cases-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.25rem;
            }

            .mkp-case-card {
                background: var(--mkp-surface);
                border: 1px solid var(--mkp-border);
                border-radius: var(--mkp-radius);
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }

            .mkp-case-image {
                aspect-ratio: 16 / 9;
                background: var(--mkp-border);
            }

            .mkp-case-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .mkp-case-body {
                padding: 1.25rem;
                flex: 1;
            }

            .mkp-case-segment {
                display: inline-block;
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: var(--mkp-primary);
                font-weight: 700;
                margin-bottom: 0.45rem;
            }

            .mkp-case-body h3 {
                margin: 0 0 0.75rem;
                font-size: 1.1rem;
            }

            .mkp-case-block {
                margin-bottom: 0.65rem;
                font-size: 0.9rem;
            }

            .mkp-case-block strong {
                display: block;
                font-size: 0.78rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: var(--mkp-muted);
                margin-bottom: 0.15rem;
            }

            .mkp-case-block p {
                margin: 0;
                color: var(--mkp-muted);
            }
</style>

{{-- A) Demo lead form --}}
<section id="demo" class="mkp-section mkp-growth-section mkp-section-alt mkp-fade">
    <div class="mkp-container">
        <div class="mkp-section-head">
            <h2 class="mkp-title">Solicitar demonstração</h2>
            <p class="mkp-subtitle">Preencha o formulário e nossa equipe entrará em contato em breve.</p>
        </div>

        @if(session('success'))
            <div class="mkp-alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('marketplace.leads.store') }}" class="mkp-roi-card">
            @csrf

            <div class="mkp-form-grid">
                <div class="mkp-field">
                    <label for="mkp-lead-name">Nome *</label>
                    <input id="mkp-lead-name" name="name" type="text" required maxlength="120"
                           value="{{ old('name') }}" autocomplete="name">
                    @error('name')<div class="mkp-subtitle" style="color:#fca5a5;font-size:0.82rem;margin-top:0.25rem;">{{ $message }}</div>@enderror
                </div>

                <div class="mkp-field">
                    <label for="mkp-lead-company">Empresa</label>
                    <input id="mkp-lead-company" name="company_name" type="text" maxlength="180"
                           value="{{ old('company_name') }}" autocomplete="organization">
                </div>

                <div class="mkp-field">
                    <label for="mkp-lead-phone">WhatsApp</label>
                    <input id="mkp-lead-phone" name="phone" type="tel" maxlength="40"
                           value="{{ old('phone') }}" autocomplete="tel">
                </div>

                <div class="mkp-field">
                    <label for="mkp-lead-email">E-mail *</label>
                    <input id="mkp-lead-email" name="email" type="email" required maxlength="180"
                           value="{{ old('email') }}" autocomplete="email">
                    @error('email')<div class="mkp-subtitle" style="color:#fca5a5;font-size:0.82rem;margin-top:0.25rem;">{{ $message }}</div>@enderror
                </div>

                <div class="mkp-field">
                    <label for="mkp-lead-segment">Segmento</label>
                    <select id="mkp-lead-segment" name="segment">
                        <option value="">Selecione…</option>
                        <option value="provedor-internet" @selected(old('segment') === 'provedor-internet')>Provedor de internet</option>
                        <option value="energia-solar" @selected(old('segment') === 'energia-solar')>Energia solar</option>
                        <option value="imobiliaria" @selected(old('segment') === 'imobiliaria')>Imobiliária</option>
                        <option value="representante-comercial" @selected(old('segment') === 'representante-comercial')>Representante comercial</option>
                        <option value="outro" @selected(old('segment') === 'outro')>Outro</option>
                    </select>
                </div>

                <div class="mkp-field">
                    <label for="mkp-lead-employees">Colaboradores</label>
                    <select id="mkp-lead-employees" name="employees">
                        <option value="">Selecione…</option>
                        <option value="1-5" @selected(old('employees') === '1-5')>1–5</option>
                        <option value="6-20" @selected(old('employees') === '6-20')>6–20</option>
                        <option value="21-50" @selected(old('employees') === '21-50')>21–50</option>
                        <option value="51+" @selected(old('employees') === '51+')>51+</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:1.5rem;">
                <button type="submit" class="mkp-btn mkp-btn-primary" data-mkp-event="marketplace.lead_submitted">
                    Solicitar demonstração
                </button>
            </div>
        </form>
    </div>
</section>

{{-- B) ROI Calculator --}}
<section class="mkp-section mkp-growth-section mkp-roi mkp-fade">
    <div class="mkp-container">
        <div class="mkp-section-head">
            <h2 class="mkp-title">Calculadora de ROI</h2>
            <p class="mkp-subtitle">Estime quanto sua operação pode estar perdendo sem acompanhamento comercial estruturado.</p>
        </div>

        <div class="mkp-roi-card">
            <div class="mkp-form-grid">
                <div class="mkp-field">
                    <label for="mkp-roi-vendedores">Quantidade de vendedores</label>
                    <input id="mkp-roi-vendedores" type="number" min="0" max="10000" value="5">
                </div>
                <div class="mkp-field">
                    <label for="mkp-roi-vendas">Vendas mensais</label>
                    <input id="mkp-roi-vendas" type="number" min="0" step="1" value="40">
                </div>
                <div class="mkp-field">
                    <label for="mkp-roi-ticket">Ticket médio (R$)</label>
                    <input id="mkp-roi-ticket" type="number" min="0" step="0.01" value="2500">
                </div>
                <div class="mkp-field">
                    <label for="mkp-roi-perdas">Perdas estimadas (%)</label>
                    <input id="mkp-roi-perdas" type="number" min="0" max="100" value="15">
                </div>
            </div>

            <div style="margin-top:1.25rem;">
                <button type="button" id="mkp-roi-calc-btn" class="mkp-btn mkp-btn-primary">Calcular</button>
            </div>

            <div id="mkp-roi-result" class="mkp-roi-result is-hidden" aria-live="polite">
                <p id="mkp-roi-message" style="margin:0;"></p>
                <div id="mkp-roi-loss" class="mkp-roi-loss"></div>
                <a class="mkp-btn mkp-btn-outline" href="{{ route('signup.create') }}" data-mkp-event="marketplace.signup_started">
                    Começar teste grátis
                </a>
            </div>
        </div>
    </div>
</section>

{{-- C) Cases --}}
@if(isset($cases) && $cases->isNotEmpty())
    <section class="mkp-section mkp-growth-section mkp-section-alt mkp-fade">
        <div class="mkp-container">
            <div class="mkp-section-head">
                <h2 class="mkp-title">Cases de sucesso</h2>
                <p class="mkp-subtitle">Empresas que transformaram a operação comercial com o Expandor.</p>
            </div>

            <div class="mkp-cases-grid">
                @foreach($cases as $case)
                    <article class="mkp-case-card mkp-fade">
                        @if($case->imageUrl())
                            <div class="mkp-case-image">
                                <img src="{{ $case->imageUrl() }}" alt="{{ $case->company_name }}" loading="lazy">
                            </div>
                        @endif
                        <div class="mkp-case-body">
                            @if($case->segment)
                                <span class="mkp-case-segment">{{ $case->segment }}</span>
                            @endif
                            <h3>{{ $case->company_name }}</h3>
                            @if($case->challenge)
                                <div class="mkp-case-block">
                                    <strong>Desafio</strong>
                                    <p>{{ $case->challenge }}</p>
                                </div>
                            @endif
                            @if($case->solution)
                                <div class="mkp-case-block">
                                    <strong>Solução</strong>
                                    <p>{{ $case->solution }}</p>
                                </div>
                            @endif
                            @if($case->result)
                                <div class="mkp-case-block">
                                    <strong>Resultado</strong>
                                    <p>{{ $case->result }}</p>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

<script>
            (function () {
                var btn = document.getElementById('mkp-roi-calc-btn');
                if (!btn) return;

                var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                var url = @json(route('marketplace.roi.calculate'));

                btn.addEventListener('click', function () {
                    var payload = {
                        quantidade_vendedores: parseInt(document.getElementById('mkp-roi-vendedores').value, 10) || 0,
                        vendas_mensais: parseFloat(document.getElementById('mkp-roi-vendas').value) || 0,
                        ticket_medio: parseFloat(document.getElementById('mkp-roi-ticket').value) || 0,
                        perdas_estimadas: parseFloat(document.getElementById('mkp-roi-perdas').value) || 0,
                    };

                    btn.disabled = true;

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data.ok) return;
                            var result = document.getElementById('mkp-roi-result');
                            document.getElementById('mkp-roi-message').textContent = data.message || '';
                            document.getElementById('mkp-roi-loss').textContent = data.formatted_loss || '';
                            result.classList.remove('is-hidden');
                        })
                        .catch(function () {})
                        .finally(function () { btn.disabled = false; });
                });
            })();
</script>
