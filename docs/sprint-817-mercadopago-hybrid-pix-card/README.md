# Sprint 8.1.7 — Mercado Pago Híbrido (PIX interno + Cartão Checkout Pro)

## Fluxo

| Método | Comportamento |
|--------|----------------|
| **PIX** | `POST /v1/payments` → QR + copia e cola na tela `/assinar/pix` |
| **Cartão** | Preference Checkout Pro → `init_point` (inalterado) |

Webhook existente permanece: `approved` provisiona, `pending` aguarda, `rejected` falha.

## Arquivos principais

- `MercadoPagoProvider::createPixPayment()` / `createCheckoutProPreference()`
- `payment_gateway_transactions` (QR, método, payment_id)
- `resources/views/payments/pix.blade.php`
- Painel Mercado Pago: lista dos últimos pagamentos

## Testes

`tests/Feature/Release/Sprint817MercadoPagoHybridPixCardTest.php`
