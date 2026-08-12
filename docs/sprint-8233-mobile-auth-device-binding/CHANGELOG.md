# CHANGELOG — 8.2.33

- Auth mobile Seller com Bearer Sanctum e device binding
- `users.session_version` passa a valer também para PAT device-bound
- Migration aditiva em `personal_access_tokens`
- CORS allowlist Capacitor
- CSP `connect-src` no shell
- Tela de login Expandor no capacitor-shell
- SecureAuthStorage + apiFetch + MobileAuthService
- CSRF except `api/mobile/*` para WebView Bearer
- Testes `Sprint8233MobileAuthDeviceBindingTest`
- Hotfix: índice `pat_tokenable_device_idx` (MySQL 1059 — nome automático > 64)
- Hotfix: migration idempotente para produção com colunas já criadas e índice ausente

Sem offline, sem push, sem APK, sem merge.
