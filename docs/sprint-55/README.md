# Sprint 5.5 — Aquisição automática SaaS (Trial 2 dias)

**Status:** Implementado (decisões aprovadas)

## Decisões aplicadas

| Item | Escolha |
|------|---------|
| Plano | **Professional** |
| Demo | Checkbox **marcado por padrão** |
| Pós-cadastro | **Setup Wizard** (`/setup`) |

## Fluxo

```
Login → Começar teste grátis → /teste-gratis
  → ProvisionTrialCompanyAction
  → Company + Subscription(trial, 2d, Professional) + Admin + Brand + Settings
  → (opcional) GenerateDemoDataAction
  → Auto-login → Setup Wizard
```

## Rotas

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/teste-gratis` | Formulário |
| POST | `/teste-gratis` | Criar trial (`throttle:trial-signup`) |
| POST | `/platform/companies/{id}/convert-trial` | Owner converte trial → cliente |

## Reuso (sem tabela nova)

- `subscriptions` (`status=trial`, `trial_ends_at`)
- Padrão de `ProvisionCompanyAction` (brand + settings)
- `Role::ADMINISTRATOR`
- Onboarding + `GenerateDemoDataAction`
- `ExpireTrialsJob` / `BillingAutomationService::expireTrials`

## Config

`config/acquisition.php`:
- `trial_days` = 2
- `plan_slug` = professional
- throttle por IP (3/min, 5/hora)

## Owner

Lista de empresas: filtro `subscription_status=trial`, colunas WhatsApp / trial até / responsável.  
Show: botão **Converter para cliente**.

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=TrialSignupSprint55Test
```

Cobertura: cadastro, tenant Professional, isolamento, e-mail duplicado, demo on/off, expiração, conversão Owner, permissões.

## Docs

- [architecture-analysis.md](./architecture-analysis.md)
- [implementation-proposal.md](./implementation-proposal.md)
- [implementation-notes.md](./implementation-notes.md)
