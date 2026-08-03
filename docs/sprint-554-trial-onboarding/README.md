# Sprint 5.5.4 — Onboarding Trial e ativação SaaS

## Objetivo

Melhorar o primeiro acesso após criar o Trial — **somente onboarding**.

Não altera: cadastro, autenticação, permissões, tenancy, vendas, mapa, clientes, comissão, estoque.

## Setup Wizard

- Título: `Bem-vindo ao {nome da plataforma}` (Platform Branding)
- Subtítulo: `Vamos preparar seu ambiente em poucos minutos.`
- Checklist visual (empresa ✅ + demais ⬜/✅)
- Progresso: `Seu ambiente está X% configurado`
  - Empresa criada = **20%** base
  - +16% cada: identidade, produto, cliente/ponto, equipe, venda

## Ações rápidas

| Card | Rota |
|------|------|
| Configurar empresa | `company.branding.edit` |
| Adicionar produto | `commissions.products.create` |
| Cadastrar cliente | `properties.create` |
| Abrir mapa | `map.index` |
| Criar equipe | `operations.team` |

## Demo

Se `onboarding_runs.demo_generated`:

> Seu ambiente foi preparado com dados de exemplo para você explorar.

Botão **Explorar demonstração** → mapa.

## Trial

Banner global (layouts app/operational):

- `Teste grátis: faltam X dias`
- `Seu teste termina amanhã`
- Expirado → redirect `/trial/converter` (`trial.conversion`)

## Serviços novos

- `EnvironmentProgressService`
- `TrialBannerService`
- `TrialConversionController`

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=TrialOnboardingTest
.\.tools\php\php.exe artisan test --filter=OnboardingModuleTest
```

## Arquivos

- `resources/views/onboarding/setup.blade.php`
- `resources/views/onboarding/partials/trial-banner.blade.php`
- `resources/views/onboarding/trial-conversion.blade.php`
- `app/Domains/Onboarding/Services/EnvironmentProgressService.php`
- `app/Domains/Onboarding/Services/TrialBannerService.php`
- `app/Http/Controllers/Web/Onboarding/SetupWizardController.php`
- `tests/Feature/Onboarding/TrialOnboardingTest.php`
