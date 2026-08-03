# Sprint 7.2 — Correção Provisionamento SaaS Checkout

## Problema

Ao finalizar checkout / gerar dados demo no onboarding, o provisionamento falhava com:

```text
SQLSTATE[23000]: Duplicate entry 'company_id-BOM JARDIM DE GOIAS-GO'
for key cities_company_id_name_state_unique
```

Causa: criação de cidades (e entidades correlatas) via `create()` sem respeitar unique `(company_id, name, state)` — tipicamente reenvio do wizard ou reexecução parcial da demo.

## Objetivo

Tornar a criação de dados demo / onboarding **idempotente**: primeiro cadastro funciona; segunda execução do mesmo fluxo **não gera duplicidade**.

## Regras aplicadas

- Entidades com unique constraint no fluxo de seed/onboarding usam `updateOrCreate` / upsert (não `create` cego).
- Cidades: `TerritoryService::upsertCity()` (também usado por `createCity()`).
- Setores: `TerritoryService::upsertSector()` (também usado por `createSector()`).
- Migrations e índices únicos **não** foram alterados.
- Pagamento, gateway, trial público, tenancy e permissões **não** foram alterados.

## Auditoria

| Entidade | Unique / chave | Comportamento |
|----------|----------------|---------------|
| Cidades | `(company_id, name, state)` | `upsertCity` |
| Setores | `(company_id, city_id, name)` | `upsertSector` |
| Produtos demo | `(company_id, name, is_demo)` | `updateOrCreate` |
| Produtos wizard | `(company_id, name)` | `updateOrCreate` |
| Vendedor demo | `(company_id, email)` estável `demo.seller.{id}@geosales.demo` | `updateOrCreate` |
| Time wizard | `(company_id, email)` | find → update, senão create |
| Campanha demo | `(company_id, name)` | `updateOrCreate` + sync setores/vendedores |
| Imóveis / visitas demo | marcados com `[Demo]` | só criados se ainda não existirem |
| Flag `demo_generated` | — | segunda chamada retorna metadata anterior |

## Arquivos principais

- `app/Domains/Sales/Territory/Services/TerritoryService.php`
- `app/Domains/Onboarding/Actions/GenerateDemoDataAction.php`
- `app/Domains/Onboarding/Services/WizardService.php`
- `tests/Feature/Onboarding/ProvisioningIdempotencySprint720Test.php`

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=ProvisioningIdempotencySprint720Test
```

Cobertura:

1. `test_provision_trial_creates_company` — trial provisiona empresa + admin + assinatura.
2. `test_onboarding_demo_does_not_duplicate_city` — cidade BOM JARDIM / demo / segunda geração sem duplicar.
3. `test_approved_checkout_creates_tenant_correctly` — webhook aprovado cria tenant; reenvio é duplicata.

## Como validar manualmente

1. Nova empresa → gerar demo → cidade/produtos/visitas criados.
2. Gerar demo de novo (ou reenviar etapa de cidade) → sem erro SQL e sem linhas duplicadas.
3. Checkout pago → empresa/usuário/assinatura/brand criados uma vez.
