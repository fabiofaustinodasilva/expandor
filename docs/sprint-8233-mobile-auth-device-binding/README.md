# Sprint 8.2.33 — Auth mobile + device binding + sessão única

Web Seller continua em cookie/sessão Laravel. App Seller usa Bearer Sanctum. Os dois respeitam `users.session_version` (último login vence).

## Entrega

- Login / me / logout mobile (`/api/mobile/v1/*`)
- Device ID = UUID de instalação
- Binding server-side em toda request autenticada do app
- SecureAuthStorage + apiFetch + tela de login no shell
- CORS allowlist + CSP `connect-src` no shell
- Seller only (`Role::SELLER`)
- Esqueci senha → browser no fluxo web `/esqueci-minha-senha`

## Fora de escopo

Offline, SQLite, fila/sync, Idempotency-Key, push, APK release, deep link completo, 8.2.34.
