# Sprint 8.1.8 — Mercado Pago Webhook Production Fix

## Problema

PIX criado, cliente paga, mas `PaymentGatewayTransaction` ficava `pending` e a empresa não era provisionada.

## Correções

1. Rota pública dedicada `GET|POST /webhooks/mercadopago` (CSRF exempt)
2. Processamento via `ProcessMercadoPagoPaymentAction` → `GET /v1/payments/{id}`
3. HMAC `x-signature` + `x-request-id` (fallback se secret vazio + access token)
4. Logs: `mercadopago.webhook.received`, `mercadopago.payment.approved`, `mercadopago.payment.failed`
5. Comando: `php artisan mercadopago:test-payment {payment_id}`
6. Painel: empresa, plano, método, valor, payment id, status, atualização
7. Tela PIX: aguardando / confirmado + CTA “Entrar no Expandor”
8. Idempotência: não duplica empresa

## Operação

```bash
php artisan mercadopago:test-payment 123456789
```

Webhook no painel MP: `https://seu-dominio/webhooks/mercadopago`
