@extends('layouts.operational')

@section('title', 'Configurações')

@section('page')
    <x-client.page-header
        title="Configurações"
        description="Ajustes da empresa — só gestores."
    />

    <div class="grid grid-2">
        @if(!empty($canCommissions))
            <a class="card" href="{{ route('commissions.products.index') }}" style="display:block;">
                <div class="header-meta">Comercial</div>
                <strong style="font-size:1.1rem;">Produtos / Estoque</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">Catálogo, {{ mb_strtolower($commercial::commissionPerSale()) }}, estoque e movimentações.</p>
            </a>
            <a class="card" href="{{ route('commissions.index') }}" style="display:block;">
                <div class="header-meta">Comercial</div>
                <strong style="font-size:1.1rem;">Financeiro</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">Aprovar, pagar e acompanhar vendas da equipe.</p>
            </a>
        @endif
        @if(!empty($canFieldOps))
            <a class="card" href="{{ route('operations.settings.sale') }}" style="display:block;">
                <div class="header-meta">Comercial</div>
                <strong style="font-size:1.1rem;">Venda</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">Campos obrigatórios ao finalizar uma {{ mb_strtolower($commercial::saleCompleted()) }}.</p>
            </a>
            <a class="card" href="{{ route('operations.settings.field') }}" style="display:block;">
                <div class="header-meta">Operação</div>
                <strong style="font-size:1.1rem;">Operação de Campo</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">Visibilidade, exibição e regras de edição/exclusão de pontos.</p>
            </a>
        @endif
        @if($canIntegrations)
            <a class="card" href="{{ route('operations.integrations') }}" style="display:block;">
                <div class="header-meta">Integrações</div>
                <strong style="font-size:1.1rem;">Central de Integrações</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">WhatsApp, Maps, Webhooks, APIs e ERP.</p>
            </a>
        @endif
        @if($canCompany && $company)
            <a class="card" href="{{ route('company.show', $company) }}" style="display:block;">
                <div class="header-meta">Empresa</div>
                <strong style="font-size:1.1rem;">{{ $company->name }}</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">Dados cadastrais e preferências.</p>
            </a>
        @endif
        @if($canBranding)
            <a class="card" href="{{ route('company.branding.edit') }}" style="display:block;">
                <div class="header-meta">Marca</div>
                <strong style="font-size:1.1rem;">Branding</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">Logo, cores e identidade.</p>
            </a>
        @endif
        <a class="card" href="{{ route('profile.edit') }}" style="display:block;">
            <div class="header-meta">Conta</div>
            <strong style="font-size:1.1rem;">Meu perfil</strong>
            <p class="header-meta" style="margin:.5rem 0 0;">Foto, telefone, WhatsApp e senha.</p>
        </a>
        @if($canBilling)
            <a class="card" href="{{ route('company.plan.show') }}" style="display:block;">
                <div class="header-meta">Plano</div>
                <strong style="font-size:1.1rem;">Plano e uso</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">Assinatura e limites.</p>
            </a>
        @endif
        @if($canOnboarding)
            <a class="card" href="{{ route('setup.show') }}" style="display:block;">
                <div class="header-meta">Setup</div>
                <strong style="font-size:1.1rem;">Preparar ambiente</strong>
                <p class="header-meta" style="margin:.5rem 0 0;">Wizard e checklist.</p>
            </a>
        @endif
    </div>
@endsection
