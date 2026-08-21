# Sprint — Financeiro SaaS + Mercado Pago + Fidelidade + Inadimplência

## Arquitetura financeira

Fluxo operacional:

1. Checkout comercial (novo cliente) → provisionamento com **fidelidade 6 meses** (`contract_started_at`, `minimum_term_months`, `minimum_term_ends_at`).
2. Cobrança mensal continua: `GenerateSubscriptionInvoicesJob` cria fatura `open` idempotente (`subscription_id` + `billing_period_key`).
3. Cliente paga via **PIX** ou **boleto** (Mercado Pago API `/v1/payments`) na área **Financeiro**.
4. Webhook `/webhooks/mercadopago` confirma pagamento (fetch na API, não confia só no payload).
5. Após vencimento: **5 dias de tolerância** (`config('payments.delinquency.grace_days')`).
6. `EnforceBillingDelinquencyJob` suspende com `suspension_reason = billing/past_due`.
7. Admin acessa só regularização (`company.finance.*`); seller/manager bloqueados.
8. Pagamento confirmado → `RestoreFinancialAccessAction` (somente suspensão financeira).

Reutiliza: `Subscription`, `Invoice`, `Payment`, `WebhookEvent`, `MercadoPagoProvider`, `ProcessMercadoPagoPaymentAction`, `PlatformBillingConsoleService`.

## Mercado Pago

- Provider: `app/Domains/Payments/Providers/MercadoPagoProvider.php`
- Credenciais: `MERCADO_PAGO_TOKEN`, `MERCADO_PAGO_WEBHOOK_TOKEN` (+ painel Platform Marketplace)
- PIX: `createPixPayment`
- Boleto: `createBoletoPayment` (`bolbradesco`)
- Checkout Pro (cartão) permanece para aquisição

## Webhook

- Rota: `POST|GET /webhooks/mercadopago`
- Validação HMAC/token existente
- Idempotência: checkout provisionado / fatura já `paid`
- Faturas: `external_reference` `inv_{id}_*` ou `gateway_payment_id` local

## Fidelidade

- Padrão 6 meses (`BILLING_MINIMUM_TERM_MONTHS`)
- **Não** é pagamento antecipado — mensalidade mensal
- Legados: campos **nullable**, sem backfill fictício
- Cancelamento tenant dentro da fidelidade: aviso (multa não cobrada nesta sprint)

## Tolerância e suspensão

- `BillingDelinquencyPolicy::graceDays()` = 5
- Suspensão: `suspended_at` + `suspension_reason`
- Administrativa ≠ financeira (`administrative` vs `billing/past_due`)

## Scheduler / queue

```cron
* * * * * cd /path && php artisan schedule:run
```

Jobs diários novos:

- `GenerateSubscriptionInvoicesJob`
- `EnforceBillingDelinquencyJob`

Também: `php artisan queue:work` (jobs `ShouldQueue`).

## ENV

```
MERCADO_PAGO_TOKEN=
MERCADO_PAGO_WEBHOOK_TOKEN=
MERCADO_PAGO_BASE_URL=https://api.mercadopago.com
BILLING_MINIMUM_TERM_MONTHS=6
BILLING_GRACE_DAYS=5
PAYMENT_PROVIDER=mercadopago
```

## Troubleshooting

| Sintoma | Ação |
|---------|------|
| PIX sem QR | Token MP / conta / logs `mercadopago.pix_incomplete` |
| Webhook sem efeito | URL pública HTTPS, secret, `queue:work` |
| Não desbloqueia | Verificar `suspension_reason` (admin não desbloqueia via pagamento) |
| Fatura duplicada | Unique `subscription_id`+`billing_period_key` |

## Produção

1. Migrar `2026_08_20_220000_add_saas_billing_fidelity_and_delinquency`
2. Configurar ENV / painel MP
3. Apontar webhook MP para `/webhooks/mercadopago`
4. Cron `schedule:run` + worker
5. Smoke: gerar fatura → PIX sandbox → webhook → desbloqueio
