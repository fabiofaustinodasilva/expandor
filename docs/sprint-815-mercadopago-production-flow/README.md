# Sprint 8.1.5 — Mercado Pago Production Flow Completion

## Objetivo

Finalizar o fluxo comercial real: checkout → preferência Mercado Pago → Checkout Pro → webhook → provisionamento automático.

## Fluxo

1. Cliente escolhe plano e preenche empresa, responsável, e-mail, CPF/CNPJ, telefone e senha
2. Expandor cria preferência real (`POST /checkout/preferences`)
3. Redireciona para `init_point` / `sandbox_init_point` (Checkout Pro)
4. Cliente paga PIX/cartão no Mercado Pago
5. Webhook `POST /webhooks/mercadopago` valida assinatura e consulta `GET /v1/payments/{id}`
6. `approved` → provisiona empresa + admin + assinatura ativa + e-mail de boas-vindas
7. `pending` → mantém aguardando (sem liberar acesso)
8. `rejected` → marca pagamento/checkout como falho (sem liberar acesso)

## Mudanças principais

| Área | Detalhe |
|------|---------|
| `MercadoPagoProvider` | Preferência sem fallback falso; sandbox_init_point; HMAC `x-signature`; fetch do pagamento |
| `ProviderFactory` | Prioriza Mercado Pago ativo no painel (fora de testes) |
| `CheckoutController` | Gateways reais sempre redirecionam para URL externa |
| `StoreCheckoutRequest` | CPF/CNPJ, telefone e senha obrigatórios |
| `WebhookService` | Falha localiza checkout via `external_reference` |
| Config | Default `PAYMENT_PROVIDER=mercadopago` |

## Segurança

- Access token nunca é exposto nas views
- Logs de pagamento usam snapshot sem dados sensíveis
- Webhook rejeitado sem secret / assinatura inválida (401)
- Fake provider permanece **somente** para testes (`phpunit.xml`)

## Operação

1. Painel → Marketplace → Mercado Pago: preencher Access Token, Webhook Secret, marcar ativo
2. No painel do Mercado Pago, webhook URL = `https://seu-dominio/webhooks/mercadopago`
3. `.env`: `PAYMENT_PROVIDER=mercadopago` (+ tokens se não usar só o painel)
4. Sandbox: `mode=sandbox` usa `sandbox_init_point`

## Testes

`tests/Feature/Release/Sprint815MercadoPagoProductionFlowTest.php`

- Cria preferência e redireciona para init_point
- Webhook approved provisiona
- Webhook rejected bloqueia
- Webhook pending não provisiona
- HMAC aceito
- Nenhum usuário fake no checkout
