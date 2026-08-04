# Sprint 7.4 — Onboarding Activation Premium Flow

## Objetivo

Evoluir o onboarding SaaS (7.3) para um fluxo premium de ativação: mais etapas de valor (negócio + branding), card no dashboard, empty state pós-conclusão e métricas de activation no Platform Dashboard.

## Arquitetura

```
Provisionamento → companies.onboarding_status=pending
Login admin → /onboarding
  1 Empresa
  2 Equipe
  3 Cliente CRM (Lead)
  4 Primeiro negócio (Opportunity + Product)
  5 Identidade visual (Tenant Brand)
  6 Finish → completed
Dashboard:
  - Activation card (dismissível por usuário)
  - Workspace ready (após complete)
Platform:
  - activation_rate / avg activation time
```

## Fluxo / steps

| Step | Constante | Rota |
|------|-----------|------|
| 1 | `STEP_COMPANY` | `/onboarding/company` |
| 2 | `STEP_TEAM` | `/onboarding/team` |
| 3 | `STEP_CUSTOMER` | `/onboarding/customer` |
| 4 | `STEP_SALES_SETUP` | `/onboarding/deal` |
| 5 | `STEP_BRANDING` | `/onboarding/branding` |
| 6 | `STEP_FINISH` | `/onboarding/finish` |
| 7 | `STEP_DONE` | completed |

## Endpoints

| Método | Path | Nome |
|--------|------|------|
| GET | `/onboarding` | `onboarding.index` |
| GET/PUT | `/onboarding/company` | company / company.update |
| GET/POST | `/onboarding/team` | team / team.store |
| GET/POST | `/onboarding/customer` | customer / customer.store |
| GET/POST | `/onboarding/deal` | deal / deal.store |
| GET/POST | `/onboarding/branding` | branding / branding.store |
| GET | `/onboarding/finish` | finish |
| POST | `/onboarding/complete` | complete |
| POST | `/onboarding/skip` | skip |
| POST | `/onboarding/dismiss` | dismiss |
| POST | `/onboarding/dismiss-ready` | dismiss-ready |

## Eventos

- `onboarding.started`
- `onboarding.company_completed`
- `onboarding.team_completed`
- `onboarding.customer_created`
- `onboarding.deal_created`
- `onboarding.branding_completed`
- `onboarding.step_skipped`
- `onboarding.dismissed`
- `onboarding.completed`

Payload de auditoria inclui `company_id`, `user_id`, `step` / metadata.

## Decisões técnicas

1. **Negócio:** `Opportunity` + `Product` (upsert) + Lead; “atividade inicial” registrada em `notes` + evento (não há domínio Activity).
2. **Tenant Branding:** `BrandingService` (tabela `brands`) — separado de Platform Branding.
3. **Dismiss:** `company_settings` key `onboarding.activation_dismissed.user.{id}` (JSON com step). Reaparece se `onboarding_step` avançar.
4. **Métricas:** `activation_rate = completed / created` (empresas não-system); tempo médio via `created_at` → `onboarding_completed_at`.
5. **Não altera:** auth, permissões, tenancy, billing, marketplace, CRM core além do uso de APIs existentes.

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=Sprint740ActivationTest
.\.tools\php\php.exe artisan test
```
