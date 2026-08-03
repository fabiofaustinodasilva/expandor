# Sprint 7.0 — Platform SaaS Part 1

Gestão comercial da plataforma (planos, assinaturas e administrador da empresa cliente) no painel Platform Owner.

## Escopo

- CRUD de planos SaaS (preço mensal/anual, trial, limites, features booleanas via `PlanCatalog`)
- Painel da empresa: métricas operacionais, gestão do admin e painel de assinatura
- Renovar trial, trocar plano, atualizar datas, cancelar/reativar assinatura + histórico (`subscription_events`)
- Contato do admin, reset de senha (já existia), bloqueio/desbloqueio, force logout e troca de administrador
- Dashboard: empresas totais, cancelados, churn % e conversão de trial %
- Perfil do Owner: seção 2FA somente leitura (estrutura preparada)
- Nav: link **Planos** com `@can('platform.managePlans')`

## Fora de escopo (não alterado)

- CRM, Acquisition trial signup, tenancy middleware
- Sales / Stock `UsageMetric` enum
- `RolePermissionSeeder` (permissão `platform.plans.manage` já existe)

## Migrations relacionadas

- `2026_08_03_280001_extend_plans_for_platform_saas.php` — `price_yearly`, `trial_days`, `max_teams`, `max_products`, `max_storage_mb`
- `2026_08_03_280002_add_two_factor_columns_to_users_table.php` — estrutura 2FA no Owner (`two_factor_*`)
- `2026_08_03_280003_create_subscription_events_table.php` — histórico administrativo de assinaturas

## Rotas (`platform.*`)

| Método | Rota | Nome |
|--------|------|------|
| GET | `/platform/plans` | `plans.index` |
| GET/POST | `/platform/plans/create` · `/platform/plans` | `plans.create` / `plans.store` |
| GET/PUT | `/platform/plans/{plan}/edit` · `/platform/plans/{plan}` | `plans.edit` / `plans.update` |
| POST | `/platform/plans/{plan}/activate\|deactivate` | `plans.activate` / `plans.deactivate` |
| PUT | `/platform/companies/{company}/admin-contact` | `companies.admin-contact` |
| POST | `/platform/companies/{company}/admin-block\|admin-unblock\|admin-force-logout` | block / unblock / force-logout |
| POST | `/platform/companies/{company}/change-administrator` | `companies.change-administrator` |
| POST | `/platform/companies/{company}/subscription/renew-trial` | `companies.subscription.renew-trial` |
| POST | `/platform/companies/{company}/subscription/change-plan` | `companies.subscription.change-plan` |
| PUT | `/platform/companies/{company}/subscription/dates` | `companies.subscription.dates` |
| POST | `/platform/companies/{company}/subscription/cancel\|reactivate` | cancel / reactivate |

## Services utilizados

- `PlatformPlanService`
- `PlatformSubscriptionService`
- `PlatformCompanyAdminService`
- `CompanyOperationalMetricsService`
- `PlanCatalog`
- `ResetCompanyAdminPasswordAction` (existente)

## Seed

`PlanSeeder` passa a popular `price_yearly`, `trial_days`, limites extras e `features` como mapa booleano das chaves de `PlanCatalog`.
