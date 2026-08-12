# SECURITY

## Bundle / shell

O `webDir` é `public/capacitor-shell` (HTML + vendor). **Não** copia:

- `.env` / `APP_KEY`
- senha de DB / SMTP
- Mercado Pago secret
- Google **server** key
- tenant credentials
- `storage/`

Browser key do Google Maps **não** vai hardcoded no bundle seller. Continua interpolada em runtime no Blade (`usesGoogleVisual()`).

## Capacitor config

Sem URLs de produção, sem secrets. `CAP_SERVER_URL` só via env local.

## Signing

Nenhum keystore gerado. `*.jks` / `*.keystore` no `.gitignore`.

## Source maps

Seller: desligados. Não publicar maps do vendor em loja sem revisão.

## Service worker

Não adicionado (evita cache triplo Capacitor + SW + PWA stub).
