# AUTH

## Hoje

- Login web: sessão cookie + CSRF.
- `POST /api/v1/auth/login` → token Sanctum `api`.
- `POST /api/mobile/v1/login` → token `mobile` (exige `sales_app.access`).
- Sem refresh. Sem device id. Tokens sem expiry em config.

## WebView

Cookie atual **não** é confiável no Capacitor (origem ≠ host Laravel, SameSite=lax, Secure unset).

## Modelo recomendado (app)

**Um login.** Não criar segundo produto de auth.

1. Seller App: `POST /api/mobile/v1/login` com `device_id` (futuro).
2. Guardar token no **Keychain/Keystore** (não localStorage).
3. `Authorization: Bearer`.
4. Logout: `POST /logout` da API + `tokens()->delete()` do dispositivo + limpar secure storage.
5. Web Seller continua cookie + `claimSellerLogin`.

CSRF não se aplica a Bearer. CORS allowlist (não `*`) para origens Capacitor se a UI for remota; com bundle local same-origin-to-API via HTTPS não precisa cookie.

## Não fazer agora

Não implementar login nativo nesta sprint.
