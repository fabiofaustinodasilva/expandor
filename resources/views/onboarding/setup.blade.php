@extends('layouts.app')

@section('title', 'Setup')

@section('content')
    @php
        $platformName = $platformName ?? 'Expandor';
        $environment = $environment ?? null;
        $trial = $trial ?? null;
    @endphp

    <div style="margin-bottom:1.15rem;" data-setup-wizard="1">
        <h1 class="page-title" style="margin:0;" data-setup-welcome="1">
            Bem-vindo ao {{ $platformName }}
        </h1>
        <p class="header-meta" style="margin:.35rem 0 0;" data-setup-subtitle="1">
            Vamos preparar seu ambiente em poucos minutos.
        </p>
    </div>

    @if($trial?->show)
        <div class="card" style="margin-bottom:1rem; padding:.85rem 1rem;" data-setup-trial="1">
            <strong>{{ $trial->message }}</strong>
            @if($trial->convertUrl)
                <a href="{{ $trial->convertUrl }}" style="margin-left:.5rem; color:var(--accent);">Ver assinatura</a>
            @endif
        </div>
    @endif

    @if($status->demoGenerated)
        <div class="card" style="margin-bottom:1rem; border-color:color-mix(in srgb, var(--accent) 40%, var(--border));" data-demo-banner="1">
            <p style="margin:0 0 .75rem;">
                Seu ambiente foi preparado com dados de exemplo para você explorar.
            </p>
            <a class="btn btn-primary" href="{{ route('map.index') }}" data-demo-explore="1">Explorar demonstração</a>
        </div>
    @endif

    @if($environment)
        <div class="card" style="margin-bottom:1rem;" data-environment-progress="1" data-environment-percent="{{ $environment->percent }}">
            <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:.65rem;">
                <strong data-environment-label="1">Seu ambiente está {{ $environment->percent }}% configurado</strong>
                <span class="header-meta">{{ $company->name }}</span>
            </div>
            <div style="background:var(--bg-soft); border-radius:999px; overflow:hidden; height:12px;">
                <div style="width:{{ $environment->percent }}%; height:100%; background:var(--accent); transition:width .25s ease;"></div>
            </div>

            <ul style="list-style:none; padding:0; margin:1rem 0 0; display:grid; gap:.45rem;" data-setup-checklist="1">
                @foreach($environment->items as $item)
                    <li style="display:flex; align-items:center; gap:.55rem; color:{{ $item->done ? 'var(--success, #22C55E)' : 'var(--muted)' }};">
                        <span aria-hidden="true">{{ $item->done ? '✅' : '⬜' }}</span>
                        <span>{{ $item->label }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="grid grid-2" style="margin-bottom:1rem;" data-setup-quick-actions="1">
            <a class="card" href="{{ route('company.branding.edit') }}" style="display:block;" data-qa="branding">
                <strong>Configurar empresa</strong>
                <div class="header-meta">Identidade, logo e cores</div>
            </a>
            <a class="card" href="{{ route('commissions.products.create') }}" style="display:block;" data-qa="product">
                <strong>Adicionar produto</strong>
                <div class="header-meta">Catálogo comercial</div>
            </a>
            <a class="card" href="{{ route('properties.create') }}" style="display:block;" data-qa="client">
                <strong>Cadastrar cliente</strong>
                <div class="header-meta">Primeiro ponto / cliente</div>
            </a>
            <a class="card" href="{{ route('map.index') }}" style="display:block;" data-qa="map">
                <strong>Abrir mapa</strong>
                <div class="header-meta">Campo e visitas</div>
            </a>
            <a class="card" href="{{ route('operations.team') }}" style="display:block;" data-qa="team">
                <strong>Criar equipe</strong>
                <div class="header-meta">Usuários e permissões</div>
            </a>
        </div>
    @endif

    @if(! $status->demoGenerated)
        <div style="margin-bottom:1rem;">
            <form method="POST" action="{{ route('setup.demo') }}" style="display:inline;">
                @csrf
                <button class="btn btn-ghost" type="submit">Gerar dados de demonstração</button>
            </form>
        </div>
    @endif

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">
                @switch($step)
                    @case('welcome') Continuar configuração @break
                    @case('company') Dados da empresa @break
                    @case('branding') Branding @break
                    @case('team') Equipe @break
                    @case('cities') Cidade @break
                    @case('sectors') Setores @break
                    @case('products') Produtos @break
                    @case('campaigns') Campanha @break
                    @case('sellers') Vendedor @break
                    @default Finalização
                @endswitch
            </h2>

            <form method="POST" action="{{ route('setup.store') }}">
                @csrf
                <input type="hidden" name="step" value="{{ $step }}">

                @if($step === 'welcome')
                    <p>Use as ações rápidas acima ou avance no assistente para concluir o setup passo a passo.</p>
                @elseif($step === 'company')
                    <div class="form-group"><label>Nome</label><input class="form-control" name="name" value="{{ old('name', $company->name) }}" required></div>
                    <div class="form-group"><label>Razão social</label><input class="form-control" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}"></div>
                    <div class="form-group"><label>Documento</label><input class="form-control" name="document" value="{{ old('document', $company->document) }}"></div>
                    <div class="form-group"><label>E-mail</label><input class="form-control" name="email" type="email" value="{{ old('email', $company->email) }}"></div>
                    <div class="form-group"><label>Telefone</label><input class="form-control" name="phone" value="{{ old('phone', $company->phone) }}"></div>
                @elseif($step === 'branding')
                    <div class="form-group"><label>Nome do sistema</label><input class="form-control" name="system_name" value="{{ old('system_name', config('app.name')) }}"></div>
                    <div class="form-group"><label>Nome de exibição</label><input class="form-control" name="display_name" value="{{ old('display_name', $company->name) }}"></div>
                    <div class="form-group"><label>Tema</label>
                        <select class="form-control" name="theme">
                            <option value="dark">Escuro</option>
                            <option value="light">Claro</option>
                        </select>
                    </div>
                    <p class="header-meta">Você pode refinar logo e cores depois em Empresa → Branding.</p>
                @elseif($step === 'team')
                    <div class="form-group"><label>Nome do colaborador</label><input class="form-control" name="name" value="{{ old('name') }}"></div>
                    <div class="form-group"><label>E-mail</label><input class="form-control" name="email" type="email" value="{{ old('email') }}"></div>
                    <div class="form-group"><label>Senha inicial</label><input class="form-control" name="password" type="text" value="password"></div>
                    <input type="hidden" name="role" value="manager">
                @elseif($step === 'cities')
                    <div class="form-group"><label>Cidade</label><input class="form-control" name="name" required></div>
                    <div class="form-group"><label>UF</label><input class="form-control" name="state" maxlength="2" required></div>
                @elseif($step === 'sectors')
                    <div class="form-group"><label>Cidade</label>
                        <select class="form-control" name="city_id" required>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}/{{ $city->state }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Setor</label><input class="form-control" name="name" required></div>
                @elseif($step === 'products')
                    <div class="form-group"><label>Produto</label><input class="form-control" name="name" required></div>
                    <div class="form-group"><label>Preço</label><input class="form-control" name="price" type="number" step="0.01" value="99.90"></div>
                    <div class="form-group"><label>Descrição</label><input class="form-control" name="description"></div>
                @elseif($step === 'campaigns')
                    <p>Crie sua primeira campanha em <a href="{{ route('campaigns.create') }}">Campanhas</a> e volte para marcar a etapa, ou avance e conclua depois.</p>
                @elseif($step === 'sellers')
                    <p>Cadastre vendedores em <a href="{{ route('users.index') }}">Usuários</a> e vincule-os às campanhas.</p>
                    <div class="form-group"><label>Nome do vendedor</label><input class="form-control" name="name"></div>
                    <div class="form-group"><label>E-mail</label><input class="form-control" name="email" type="email"></div>
                    <input type="hidden" name="role" value="seller">
                    <input type="hidden" name="password" value="password">
                @else
                    <p>Revise o checklist e conclua o setup para liberar o uso completo do CRM.</p>
                @endif

                <div class="actions" style="margin-top:1rem;">
                    @if($previous)
                        <a class="btn btn-ghost" href="{{ route('setup.show', ['step' => $previous]) }}">Voltar</a>
                    @endif
                    <button class="btn btn-ghost" type="submit" name="finish_later" value="1">Salvar e continuar depois</button>
                    <button class="btn btn-primary" type="submit">
                        {{ $step === 'finish' || $next === null ? 'Concluir setup' : 'Avançar' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Checklist clássico</h2>
            <div class="actions" style="margin-top:0.5rem; flex-wrap:wrap;">
                @foreach($status->checklist->steps as $item)
                    <span class="badge {{ in_array($item->status, ['completed','skipped']) ? 'badge-success' : 'badge-warning' }}">
                        {{ $item->title }}
                    </span>
                @endforeach
            </div>

            <h3 style="margin-top:1.25rem;">Alertas</h3>
            @if($status->alerts)
                <ul style="padding-left:1.1rem; color:var(--warning);">
                    @foreach($status->alerts as $alert)
                        <li>{{ $alert }}</li>
                    @endforeach
                </ul>
            @else
                <p class="header-meta">Nenhum alerta crítico no momento.</p>
            @endif
        </div>
    </div>
@endsection
