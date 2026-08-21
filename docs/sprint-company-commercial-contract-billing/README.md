# Sprint — Cadastro comercial/financeiro de empresa + contrato + primeira fatura

## Problema

`/platform/companies/create` criava Company + Subscription `active` apenas com `plan_id/starts_at`, deixando nulos:

- `contract_started_at`
- `minimum_term_months` / `minimum_term_ends_at`
- `billing_cycle`
- `next_billing_at`
- `gateway`

e **sem** primeira Invoice. No Platform Billing isso podia parecer “Em dia” sem obrigação financeira.

## Arquitetura reutilizada

Não foi criado segundo billing. Fluxo passa por:

- `PlatformCompanyService::createCompanyWithAdmin` (transação)
- `CommercialContractService` (vencimento/fidelidade/valor)
- `InvoiceService` + colunas reais (`amount_due`, `due_at`, `billing_period_key`, status `open`)
- `InvoicePaymentService` / Mercado Pago / webhook existentes (cobrança só quando o cliente pede PIX/boleto)

## Regras

### Vencimento / primeira cobrança

`firstDueDate(start, billingDay)`: candidato = dia `billingDay` no mês do início; se `< start`, avança um mês.

Ex.: início 21/08, dia 10 → 10/09.

### Fidelidade

Opções: none / 3 / 6 / 12 / custom (1–60).  
`none` deixa `minimum_term_*` null (sem fidelidade fictícia).  
Não é cobrança antecipada.

### Exceção comercial

`contracted_amount` na Subscription (snapshot). Plan.price intacto. Motivo obrigatório + AuditLog `platform.company.commercial_exception`.

### Primeira fatura

Criada no mesmo `DB::transaction` da empresa: status `open`, valor = `contracted_amount`, `next_billing_at` = firstDue + 1 mês.

### Status financeiro (Platform)

- Pendente: Invoice open/overdue não paga e ainda não em suspensão
- Em dia: sem fatura aberta
- Em período de regularização: dentro dos 5 dias
- Inadimplente: fora da tolerância
- Suspensa: `billing/past_due`

## Legados

Campos novos nullable; sem backfill; criação legada via `makeCompanyWithPlan` permanece sem fatura/fidelidade.

## Migration

`2026_08_21_120000_add_subscription_commercial_contract_fields.php`

- `contracted_amount`
- `billing_day`
- `has_commercial_exception`
- `commercial_exception_reason`

## Deploy

```bash
php artisan migrate --force
# cron/schedule e webhook MP já existentes; QUEUE sync ok
```

Não editar a migration `2026_08_20_220000_*` já aplicada.
