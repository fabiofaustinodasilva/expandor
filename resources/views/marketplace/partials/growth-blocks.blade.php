@php
    $uiCopy = $ui ?? ($premium['ui'] ?? []);
    $formCopy = $demoForm ?? ($premium['demo_form'] ?? []);
@endphp

<style>
            .mkp-demo-section { padding: 4rem 0; }
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
                transition: border-color 0.18s ease, box-shadow 0.18s ease;
            }
            .mkp-field input:focus,
            .mkp-field select:focus,
            .mkp-field textarea:focus {
                outline: none;
                border-color: color-mix(in srgb, var(--mkp-primary) 55%, transparent);
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--mkp-primary) 18%, transparent);
            }
            .mkp-field input:focus-visible,
            .mkp-field select:focus-visible,
            .mkp-field textarea:focus-visible {
                outline: 2px solid color-mix(in srgb, var(--mkp-button) 70%, transparent);
                outline-offset: 2px;
            }
            .mkp-alert-success {
                padding: 0.85rem 1rem;
                border-radius: 0.65rem;
                background: color-mix(in srgb, #22c55e 18%, transparent);
                border: 1px solid color-mix(in srgb, #22c55e 35%, transparent);
                color: #bbf7d0;
                margin-bottom: 1.25rem;
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
                transition: transform 0.2s;
            }
            .mkp-case-card:hover { transform: translateY(-3px); }
            .mkp-case-image { aspect-ratio: 16 / 9; background: var(--mkp-border); }
            .mkp-case-image img { width: 100%; height: 100%; object-fit: cover; }
            .mkp-case-body { padding: 1.25rem; flex: 1; }
            .mkp-case-segment {
                display: inline-block;
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: var(--mkp-primary);
                font-weight: 700;
                margin-bottom: 0.45rem;
            }
            .mkp-case-body h3 { margin: 0 0 0.75rem; font-size: 1.1rem; }
            .mkp-case-block { margin-bottom: 0.65rem; font-size: 0.9rem; }
            .mkp-case-block strong {
                display: block;
                font-size: 0.78rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: var(--mkp-muted);
                margin-bottom: 0.15rem;
            }
            .mkp-case-block p { margin: 0; color: var(--mkp-muted); }
            @media (max-width: 640px) {
                .mkp-form-grid { grid-template-columns: 1fr; }
            }
</style>

<section id="demo" class="mkp-section mkp-demo-section mkp-section-alt mkp-fade">
    <div class="mkp-container">
        <div class="mkp-section-head">
            <h2 class="mkp-title">{{ $formCopy['title'] ?? ($uiCopy['request_demo'] ?? 'Solicitar demonstração') }}</h2>
            <p class="mkp-subtitle">{{ $formCopy['subtitle'] ?? '' }}</p>
        </div>

        @if(session('success'))
            <div class="mkp-alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('marketplace.leads.store') }}" class="mkp-demo-card" style="max-width:720px;margin:0 auto;background:var(--mkp-surface);border:1px solid var(--mkp-border);border-radius:var(--mkp-radius);padding:2rem;">
            @csrf
            <div class="mkp-form-grid">
                <div class="mkp-field">
                    <label for="mkp-lead-name">Nome</label>
                    <input id="mkp-lead-name" name="name" type="text" value="{{ old('name') }}" required>
                </div>
                <div class="mkp-field">
                    <label for="mkp-lead-email">E-mail</label>
                    <input id="mkp-lead-email" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div class="mkp-field">
                    <label for="mkp-lead-phone">Telefone / WhatsApp</label>
                    <input id="mkp-lead-phone" name="phone" type="text" value="{{ old('phone') }}">
                </div>
                <div class="mkp-field">
                    <label for="mkp-lead-company">Empresa</label>
                    <input id="mkp-lead-company" name="company_name" type="text" value="{{ old('company_name') }}">
                </div>
                <div class="mkp-field">
                    <label for="mkp-lead-segment">Segmento</label>
                    <select id="mkp-lead-segment" name="segment">
                        <option value="">Selecione</option>
                        @foreach(($formCopy['segments'] ?? []) as $segmentOption)
                            <option value="{{ $segmentOption }}" @selected(old('segment') === $segmentOption)>{{ $segmentOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mkp-field">
                    <label for="mkp-lead-employees">Tamanho da equipe</label>
                    <select id="mkp-lead-employees" name="employees">
                        <option value="">Selecione</option>
                        <option value="1-5" @selected(old('employees') === '1-5')>1–5</option>
                        <option value="6-20" @selected(old('employees') === '6-20')>6–20</option>
                        <option value="21-50" @selected(old('employees') === '21-50')>21–50</option>
                        <option value="51+" @selected(old('employees') === '51+')>51+</option>
                    </select>
                </div>
            </div>

            <div class="mkp-field" style="margin-top:1rem;">
                <label for="mkp-lead-message">Mensagem (opcional)</label>
                <textarea id="mkp-lead-message" name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>

            <div style="margin-top:1.5rem;">
                <button type="submit" class="mkp-btn mkp-btn-primary" data-mkp-event="marketplace.lead_submitted">
                    {{ $formCopy['submit'] ?? ($uiCopy['request_demo'] ?? 'Solicitar demonstração') }}
                </button>
            </div>
        </form>
    </div>
</section>

@if(isset($cases) && $cases->isNotEmpty())
    <section class="mkp-section mkp-demo-section mkp-section-alt mkp-fade">
        <div class="mkp-container">
            <div class="mkp-section-head">
                <h2 class="mkp-title">{{ $uiCopy['success_stories'] ?? 'Histórias de quem já usa' }}</h2>
                <p class="mkp-subtitle">{{ $uiCopy['success_stories_subtitle'] ?? '' }}</p>
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
                                    <strong>{{ $uiCopy['challenge'] ?? 'Desafio' }}</strong>
                                    <p>{{ $case->challenge }}</p>
                                </div>
                            @endif
                            @if($case->solution)
                                <div class="mkp-case-block">
                                    <strong>{{ $uiCopy['solution'] ?? 'Solução' }}</strong>
                                    <p>{{ $case->solution }}</p>
                                </div>
                            @endif
                            @if($case->result)
                                <div class="mkp-case-block">
                                    <strong>{{ $uiCopy['result'] ?? 'Resultado' }}</strong>
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
