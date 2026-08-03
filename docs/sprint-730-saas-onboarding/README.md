# Sprint 7.3 — Onboarding SaaS Premium

## Objetivo

Experiência guiada de primeiro acesso para empresas SaaS novas após o provisionamento, sem alterar pagamento, checkout, marketplace, billing, CRM core, tenancy ou permissões.

## Migrations

`database/migrations/2026_08_03_300001_add_onboarding_status_to_companies_table.php`

| Coluna | Tipo | Default |
|--------|------|---------|
| `onboarding_status` | string | `pending` |
| `onboarding_step` | tinyint | `1` |
| `onboarding_completed_at` | timestamp nullable | `null` |

**Backfill:** empresas já existentes recebem `completed` + `onboarding_completed_at = now()` para não sofrerem redirect.

## Rotas

| Método | Path | Nome |
|--------|------|------|
| GET | `/onboarding` | `onboarding.index` |
| GET | `/onboarding/company` | `onboarding.company` |
| PUT | `/onboarding/company` | `onboarding.company.update` |
| GET | `/onboarding/team` | `onboarding.team` |
| POST | `/onboarding/team` | `onboarding.team.store` |
| GET | `/onboarding/customer` | `onboarding.customer` |
| POST | `/onboarding/customer` | `onboarding.customer.store` |
| GET | `/onboarding/finish` | `onboarding.finish` |
| POST | `/onboarding/complete` | `onboarding.complete` |

O wizard legado `/setup` permanece intacto.

## Fluxo

1. Empresa nova nasce com `onboarding_status = pending` (default da coluna).
2. Primeiro login do admin com `onboarding.manage` → redirect `/onboarding`.
3. Etapas: empresa → equipe → cliente CRM (Lead) → finish → `completed`.
4. Banner global “Configuração da conta · X%” enquanto incompleto (não bloqueia o sistema).
5. Empresa antiga (`completed`) não é redirecionada.

### Progresso

| Situação | % |
|----------|---|
| Empresa criada / step 1 | 20% |
| Após dados da empresa | 40% |
| Após equipe | 60% |
| Após cliente | 80% |
| Concluído | 100% |

## Auditoria (eventos)

- `onboarding.started`
- `onboarding.company_completed`
- `onboarding.team_completed`
- `onboarding.customer_created`
- `onboarding.completed`

Listeners em `RecordSaasOnboardingAudit` → `SecurityService::recordAudit`.

## Decisões técnicas

- **Cliente CRM:** `Lead` via `LeadService` (idempotente por email/telefone). Cidade via `TerritoryService::upsertCity` + nota no lead.
- **Perfis de equipe:** labels Administrador / Vendedor / Operador / Financeiro mapeados para roles existentes `administrator`, `seller`, `supervisor`, `manager` — sem criar roles nem alterar permissões.
- **Factory:** empresas de teste nascem `completed` por padrão; use `Company::factory()->pendingOnboarding()`.
- **Pagamentos/checkout:** não alterados; default da coluna cobre provisionamento novo.

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=SaasOnboardingSprint730Test
.\.tools\php\php.exe artisan test
```

Arquivo: `tests/Feature/Onboarding/SaasOnboardingSprint730Test.php`
