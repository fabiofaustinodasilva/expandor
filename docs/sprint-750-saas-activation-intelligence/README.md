# Sprint 7.5 — SaaS Activation Intelligence + Customer Success

## Objetivo

Transformar dados de onboarding/ativação em inteligência operacional para a plataforma Expandor: quem ativou, quem travou, onde desiste e quem precisa de ajuda.

## Arquitetura

```
App\Domains\Platform\
  Services\ActivationIntelligenceService   # score, timeline, alerts, health dashboard
  Services\ActivationEventRecorder         # activation.* / customer.*
  Activation\                              # outreach stubs (email, WhatsApp, internal)
  Jobs\DetectInactiveTenantsJob
  Listeners\SyncActivationEventsFromOnboarding
  DTOs\CompanyActivationSnapshot, SaasHealthDashboardMetrics
  Enums\ActivationHealthStatus
```

Onboarding continua em `App\Domains\Onboarding` (write-path). Platform consome e observa.

## Fluxo

1. Eventos de onboarding → sync `activation.started|first_customer|first_deal|completed`
2. Login → `activation.first_login` / `customer.reactivated`
3. Job diário → `customer.inactive` quando sem login 10+ dias
4. Platform `/platform/activation` → métricas + stuck + alertas
5. `platform.companies.show` → score, timeline, uso, alertas
6. Dashboard cliente → progresso + próximos passos

## Endpoints

| Rota | Nome |
|------|------|
| `GET /platform/activation` | `platform.activation.index` |
| `GET /platform/companies/{company}` | enriquecido com activation |

## Activation Score (0–100)

| Fator | Pontos |
|-------|--------|
| Onboarding concluído | +25 |
| Usuários (≥2 / 1) | +15 / +8 |
| Cliente CRM | +15 |
| Negócio | +15 |
| Login ≤7d / ≤30d | +15 / +8 |
| Módulos usados | até +15 |

Status: 🟢 ≥80 · 🟡 ≥50 · 🔴 <50

## Eventos

- `activation.started`
- `activation.first_login`
- `activation.first_customer`
- `activation.first_deal`
- `activation.completed`
- `customer.inactive`
- `customer.reactivated`

## Automação futura

`ActivationOutreachService` + channels:

- `email` (stub log)
- `whatsapp` (stub log)
- `internal` (audit `activation.outreach.internal`)

Ex.: `nudgeFirstCustomer()` — template pronto para CS.

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=Sprint750ActivationIntelligenceTest
.\.tools\php\php.exe artisan test
```
