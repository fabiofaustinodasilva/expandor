# Sprint 7.1 — Marketplace UI + Platform Billing

Marketplace público Expandor, checkout com ciclo/método de pagamento, página de espera PIX e console de billing no painel Platform Owner.

## Escopo

- Campos de plano para marketplace: `display_order`, `is_featured`, `max_visits`
- Landing `/`, listagem `/planos`, fluxo `/assinar` → checkout
- Checkout com `billing_cycle` (monthly/yearly), `payment_method` (PIX / CREDIT_CARD) e senha admin opcional
- Página `/assinar/aguardando` + polling JSON `/assinar/status/{uuid}`
- Platform Billing: pagamentos, assinaturas (filtros trial/active/suspended/cancelled), checkouts pendentes e renovações
- Dashboard: barras CSS de clientes/MRR por plano + card Receita prevista (ARR / MRR×12)
- `MercadoPagoProvider` (já no domínio Payments) + webhook genérico `/webhooks/{provider}`

## Fora de escopo (não alterado)

- Acquisition / trial `/cadastro` e `/teste-gratis`
- CRM, Sales, Tenancy
- `RolePermissionSeeder` / Onboarding services

## Arquitetura reutilizada

| Peça | Uso |
|------|-----|
| `CheckoutService` / `CreateCheckoutAction` | Início de checkout e listagem de planos pagos |
| `PaymentRepository::paginateClientPlans` | Planos ativos ordenados por `display_order`, preço; inclui featured |
| `WebhookController@handle` | Assaas, Fake, Mercado Pago (`/webhooks/mercadopago`) |
| `WebhookService` + providers | Confirmação → provisionamento |
| `RenewSubscriptionsJob` | Renovações / cobranças recorrentes |
| `PlanCatalog` | Features booleanas nos cards do marketplace |
| `PlatformDashboardMetrics` | MRR/ARR/clientsByPlan no dashboard |

## Rotas públicas

| Método | Rota | Nome |
|--------|------|------|
| GET | `/` | `marketplace.home` |
| GET | `/planos` | `marketplace.plans` |
| GET | `/assinar` | `marketplace.subscribe` |
| GET | `/assinar/aguardando` | `checkout.waiting` |
| GET | `/assinar/status/{uuid}` | `checkout.status` |
| GET | `/plans` | `plans.index` (legado, view atualizada) |
| GET/POST | `/checkout` | `checkout.create` / `checkout.store` |
| POST | `/webhooks/{provider}` | `webhooks.provider` |

## Rotas platform

| Método | Rota | Nome |
|--------|------|------|
| GET | `/platform/billing` | `platform.billing.index` |

Autorização: `platform.manageCompanies`.

## Mercado Pago

- Provider: `App\Domains\Payments\Providers\MercadoPagoProvider`
- Checkout Preferences API; `notification_url` → `/webhooks/mercadopago`
- Fallback de URL: `/assinar/aguardando?session={uuid}` quando não há `init_point`
- Segurança do webhook: `verifyWebhook()` do provider (token/assinatura conforme config `payments.providers.mercadopago`)

## Fake provider (dev/test)

- PIX → redireciona para `/assinar/aguardando`
- Cartão → `/checkout/success` (fluxo imediato simulado)
- Webhook: header `X-Webhook-Token` = config `webhook_token`

## Auditoria

Actions registradas:

- `payments.checkout.started`
- `payments.payment.approved`
- `payments.payment.refused`
- `payments.company.provisioned`

Somente webhook oficial (token validado) altera status de pagamento/assinatura — callback do browser não provisiona.

## Segurança

- `MercadoPagoProvider::verifyWebhook` exige `MERCADO_PAGO_WEBHOOK_TOKEN`
- Sem token → 401
- Página de sucesso/aguardando apenas consulta status; não confirma pagamento

## Migration

- `2026_08_03_290001_add_marketplace_fields_to_plans_table.php` — `display_order`, `is_featured`, `max_visits`

## Seed

`PlanSeeder`:

| Plano | display_order | is_featured | max_visits |
|-------|---------------|-------------|------------|
| Professional | 10 | true | 10000 |
| Enterprise | 20 | false | null |
| Free | 30 | false | 200 |

## Fluxo de compra

1. Visitante escolhe plano em `/planos` ou `/assinar?plan_id=`
2. Preenche checkout (ciclo + PIX/cartão)
3. Gateway retorna URL externa ou página de espera
4. Webhook confirma pagamento → `ProvisionCompanyAction` cria empresa/usuário/assinatura
5. Polling em `/assinar/status/{uuid}` redireciona para success quando `provisioned`

Trial gratuito continua exclusivo em `/cadastro` (sem mudança neste sprint).
