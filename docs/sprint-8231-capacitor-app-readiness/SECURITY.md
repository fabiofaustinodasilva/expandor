# SECURITY

## Token

Keychain (iOS) / Keystore (Android). Clear on logout e `session_replaced`.

## CORS (quando UI ≠ API origin)

Publicar `config/cors.php` **só** na slice de auth:

```
paths: api/*, sanctum/csrf-cookie
allowed_origins: lista (nunca *)
supports_credentials: false  // Bearer
```

Origens possíveis: `capacitor://localhost`, `http://localhost`, `https://localhost`, host de produção. Allowlist explícita.

Hoje `config/cors.php` **não existe** — middleware não casa paths.

## CSP

Não abrir `*` para script. Depois de vendor local: `script-src 'self'`; Google Maps só se provider Google (`maps.googleapis.com`).

## Não no repo

Keystore, certificados Apple, `google-services.json` com secrets, `.p8`.
