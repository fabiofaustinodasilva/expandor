# Dead code — Candidatos (não remover nesta sprint)

> Itens abaixo são **suspeitos** por inventário estático. Confirmar com cobertura de rotas/tests antes de deletar na 8.2.x.

## Produto / permissões órfãs

| Item | Motivo |
|------|--------|
| `reports.view`, `reports.export` | Sem controllers/views/rotas de Relatórios |
| FeatureFlag keys no client | Seedadas; **não** referenciadas em menus tenant |

## Políticas / classes subutilizadas

| Item | Motivo |
|------|--------|
| `SalesAppPolicy` | Não registrada no Gate |
| `CustomerPolicy` | Não registrada; uso manual |

## Duplicação estrutural (não é “morto”, é “redundante”)

| Par | Nota |
|-----|------|
| `SetupWizardController` vs `SaasOnboardingController` | Dois onboardings |
| `company.plan` vs `company.subscription` | Dois hubs billing |
| `users.*` vs `operations.team` | Dois CRUDs de pessoa |
| `crm.commissions` vs `commissions` | Dois sentidos de comissão |
| Follow-ups web vs sales-app | Duas UIs |

## Events sem listeners

Payment domain events (`PaymentCreated`, `SubscriptionActivated`, …) — dispatched, **sem** listeners em `AppServiceProvider`. Podem ser intencionais (audit futuro) ou mortos.

## Views / rotas

- Não há evidência forte de Blade tenant **completamente** sem rota; a maioria liga a `web.php`.
- Platform/Marketplace views **fora** desta auditoria.

## Seeds

- `RolePermissionSeeder`, `PlanSeeder`, `OnboardingSeeder`, `FeatureFlagSeeder` — necessários.
- Seeds demo: verificar se ainda usados só em `geosales:install` / onboarding demo.

## Migrations

- Não listar como “mortas” — histórico necessário.  
- Integrity repair (8.1.9.2) é ferramenta; não dead.

## Jobs

Todos os jobs de Payments/AI/Communication aparentam ter schedule ou dispatch — manter.

## Recomendação

Na 8.2.1: criar checklist “confirm unused 30 days” (logs de rota) antes de qualquer delete. Esta sprint **não remove nada**.
