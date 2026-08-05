# Sprint 8.1.6 — Fix Payment Provider Selection (Production)

## Problema

Em produção, checkouts novos apareciam com `gateway: fake` mesmo com Mercado Pago ativo no painel.

Causa: `ProviderFactory` retornava `fake` imediatamente quando `PAYMENT_PROVIDER=fake` no `.env`, **antes** de consultar `payment_gateway_settings`.

## Correção

| Regra | Comportamento |
|-------|----------------|
| `APP_ENV=testing` (ou `PAYMENT_ALLOW_FAKE=true`) | Fake permitido |
| `APP_ENV=production` / qualquer não-testing | Fake **bloqueado** |
| Mercado Pago ativo + token no painel | Sempre `mercadopago` |
| `.env` com `PAYMENT_PROVIDER=fake` fora de testing | Resolve `mercadopago` |
| Histórico antigo | Não alterado |

## Arquivos

- `app/Domains/Payments/Providers/ProviderFactory.php`
- `config/payments.php` (`allow_fake`)
- `.env.example`

## Operação

Garantir no servidor:

```env
PAYMENT_PROVIDER=mercadopago
APP_ENV=production
```

Painel: Marketplace → Mercado Pago → ativo + Access Token.

Novos checkouts devem gravar `gateway = mercadopago`.

## Testes

`tests/Feature/Release/Sprint816FixPaymentProviderSelectionTest.php`
