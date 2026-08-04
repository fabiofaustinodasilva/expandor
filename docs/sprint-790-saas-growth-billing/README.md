# Sprint 7.9 — SaaS Growth & Billing Intelligence

## Objetivo

Camada de inteligência de crescimento SaaS: limites de plano, uso por empresa, alertas, milestones de trial, health score unificado e dashboard Platform — sem alterar auth, tenants, permissões, onboarding core, Marketplace CMS/Revenue ou CRM interno.

## Arquitetura

```
App\Domains\SaasGrowth\
  Enums\       UsageMetric, TrialMilestone, HealthClassification
  Models\      CompanyUsageMetric, TrialMilestoneRecord
  Services\    SaasUsageService, LimitAlertService, TrialIntelligenceService,
               CompanyHealthScoreService, UpgradeIntelligenceService,
               SaasIntelligenceDashboardService
  Listeners\   SyncSaasTrialMilestones
  DTOs\        CompanyUsageSnapshot, SaasIntelligenceMetrics
```

## Planos (evolução)

Campos adicionados em `plans` (sincronizados com legado):

| Novo | Legado |
|------|--------|
| `users_limit` | `max_users` |
| `customers_limit` | `max_properties` |
| `storage_limit` | `max_storage_mb` |
| `active` | `status=active` |

Helpers: `Plan::usersLimit()`, `customersLimit()`, `storageLimit()`, `isActivePlan()`.

## Uso e alertas

Tabela `company_usage_metrics` — snapshots por métrica.

| % uso | Evento | Mensagem |
|-------|--------|----------|
| ≥80 | `saas.limit_warning` | Próximo do limite |
| ≥90 | `saas.limit_reached` | Faça upgrade |

## Trial milestones

Tabela `trial_milestones`: `company_created`, `team_created`, `customer_created`, `deal_created`, `first_sale`, `activated`.

Sincronizado via listeners de onboarding + `trial.started` / `trial.converted` em signup/conversão.

## Health

`CompanyHealthScoreService` calcula via `HealthScoreService` existente e grava `classification`:

- 0–30 `risk`
- 31–70 `attention`
- 71–100 `healthy`

Evento: `health_score_calculated`.

## Upgrade intelligence

≥80% de limite → `subscription.upgrade_recommended` + `upgrade_recommended`.

## Dashboard

`GET /platform/saas-intelligence` (`platform.saas.intelligence`)

Métricas: total/ativas, trials ativos/convertidos, taxa conversão, health médio, risco, MRR estimado, hints de upgrade.

## Testes

`tests/Feature/SaasGrowth/Sprint790SaasGrowthBillingTest.php`
