# MERCADOPAGO-AUDIT

## Classificação

**PLATFORM-MANAGED**

Evidência:
- Tabela `payment_gateway_settings` **sem** `company_id` (provider unique global)
- UI Admin: `/platform/marketplace/mercadopago` (`marketplace.manage`)
- Credenciais: `.env` (`MERCADO_PAGO_*`) + DB (`access_token`/`webhook_secret` encrypted)
- Um único conjunto de credenciais atende checkout SaaS (compra de plano → provision)

## O que NÃO é
Não é TENANT-MANAGED: empresa cliente **não** cola access token MP próprio para receber pagamentos Expandor.

## Hybrid operacional?
Apenas no sentido **storage**: painel pode sobrescrever `.env` via `ProviderFactory`. Continua um wallet da plataforma.

## Superfície

| Peça | Path |
|------|------|
| Provider | `MercadoPagoProvider` |
| Factory | `ProviderFactory` |
| Webhook | `/webhooks/mercadopago` |
| Settings | `MercadoPagoSettingsController` |
| Checkout | `CreateCheckoutAction` / PIX / Preference |

## Assinatura recorrente MP
`createSubscription` é stub local — recorrência nativa MP não é o fluxo atual.

## Recomendação
**Não** forçar Mercado Pago no modelo Google Maps.  
Manter PLATFORM-MANAGED. Futuro: se houver “MP Connect / OAuth por empresa” para marketplace de produtos da ISP, seria produto **novo** (HYBRID/TENANT), separado do checkout SaaS.
