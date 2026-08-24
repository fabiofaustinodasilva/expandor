# Sprint — Ciclo mensal de faturas + fidelidade contínua

## Problema

A geração de faturas ocorria apenas quando `next_billing_at <= now()`, ou seja, no dia do vencimento (ou depois). O cliente não via a próxima mensalidade com antecedência. Também faltava deixar explícito que **fidelidade ≠ fim da assinatura**.

## Decisões

| Tema | Decisão |
|------|----------|
| Antecedência | `BILLING_INVOICE_GENERATION_DAYS=10` (config `payments.invoice_generation_days`) |
| `next_billing_at` | Semântica = **próximo vencimento**, não data de geração |
| Avanço do ciclo | Somente após pagamento/fechamento da fatura (webhook) |
| Fidelidade | `minimum_term_ends_at` = fim do compromisso mínimo; assinatura **continua active** |
| Projeções | UI “Próximas mensalidades” é calendário (não cria Invoice no banco) |
| Gateway | PIX/boleto só sob demanda do cliente |
| Renew job | Não cria Payment automático; delega à geração de Invoice |

## Quando a próxima fatura é criada?

Quando:

1. Subscription `active`, não cancelada
2. `next_billing_at` definido
3. `today >= next_billing_at - BILLING_INVOICE_GENERATION_DAYS`

Ex.: vencimento `10/10/2026` → disponível a partir de `30/09/2026`.

## Idempotência

Unique `(subscription_id, billing_period_key)` + `lockForUpdate` em `RecurringBillingService::ensureOpenInvoice`.

## Pós-fidelidade

Após `minimum_term_ends_at`:

- status permanece `active`
- `next_billing_at` continua
- job continua gerando faturas
- UI mostra “Fidelidade concluída” + texto de continuidade

## Erros Mercado Pago

`payer.email` inválido ou HTTP 4xx → `PaymentGatewayClientException` → flash amigável no Financeiro (sem 500 / sem JSON ao cliente). Detalhe técnico só em log.

## Scheduler

Já existente:

- `GenerateSubscriptionInvoicesJob` (daily)
- `EnforceBillingDelinquencyJob` (daily)
- cron `schedule:run`

Sem queue worker obrigatório se `QUEUE_CONNECTION=sync`.

## Deploy

```bash
# opcional no .env (default já é 10)
BILLING_INVOICE_GENERATION_DAYS=10

php artisan config:clear
# sem migration nova nesta sprint
```
