# CORS

Arquivo novo: `config/cors.php`.

## Allowlist

- `capacitor://localhost`
- `http://localhost`
- `https://localhost`
- `http://127.0.0.1`
- `https://127.0.0.1`
- `APP_URL`
- extras em `CORS_ALLOWED_ORIGINS` (CSV)

`supports_credentials` = **false** (Bearer, sem cookie).

**Não** usar `allowed_origins = ['*']`.

Paths: `api/*` apenas.

Headers: `Authorization`, `Content-Type`, `Accept`, `X-Device-Id`, `X-App-Version`, `X-Requested-With`.

## Sanctum stateful

Não adicionar origens `capacitor://` em `SANCTUM_STATEFUL_DOMAINS`. O app é stateless.

`api/mobile/*` está no except de CSRF. Requests com `Authorization: Bearer` passam por `PreferStatelessBearer` (Sanctum stateful desligado naquele request) para o token não virar sessão cookie.
