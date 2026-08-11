# Sprint 8.2.31 — Capacitor App Readiness

Auditoria + arquitetura. **Sem Capacitor instalado. Sem APK. Sem loja. Sem offline completo.**

## Decisão

**Híbrido progressivo (C):** shell Capacitor nativo + API Laravel HTTPS + assets locais.

WebView apontando para o site remoto **não** é o MVP de loja (cookie `SameSite=lax` + CSRF + CDN quebram no WebView). Pode ser smoke de laboratório depois do 8.2.32 — não agora.

## Docs

| Arquivo | Conteúdo |
|---------|----------|
| [AUDIT.md](./AUDIT.md) | Inventário real |
| [ARCHITECTURE-DECISION.md](./ARCHITECTURE-DECISION.md) | A vs B vs C |
| [AUTH.md](./AUTH.md) | Cookie vs Bearer |
| [SESSION.md](./SESSION.md) | Sessão única + gap PAT |
| [API-INVENTORY.md](./API-INVENTORY.md) | READY / PARTIAL / MISSING |
| [ASSETS-CDN.md](./ASSETS-CDN.md) | Tailwind, Lucide, Leaflet |
| [MAP-GPS.md](./MAP-GPS.md) | Mapa + geolocation |
| [OFFLINE.md](./OFFLINE.md) | Escopo realista |
| [SYNC.md](./SYNC.md) | Fila + conflitos |
| [IDEMPOTENCY.md](./IDEMPOTENCY.md) | client_operation_id |
| [LOCAL-STORAGE.md](./LOCAL-STORAGE.md) | SQLite vs IDB |
| [SECURITY.md](./SECURITY.md) | Keychain / CORS / CSP |
| [DEEP-LINKS.md](./DEEP-LINKS.md) | Password reset |
| [NETWORK-UX.md](./NETWORK-UX.md) | Online / pendências |
| [ANDROID.md](./ANDROID.md) | Package / Play |
| [IOS.md](./IOS.md) | Bundle / App Store |
| [PRIVACY.md](./PRIVACY.md) | Location / PII |
| [APP-READINESS-MATRIX.md](./APP-READINESS-MATRIX.md) | Matriz |
| [IMPLEMENTATION-ROADMAP.md](./IMPLEMENTATION-ROADMAP.md) | 8.2.32–8.2.38 |
| [TEST-REPORT.md](./TEST-REPORT.md) | Testes |
| [CHANGELOG.md](./CHANGELOG.md) | Delta |

## Branch / commit

- `feature/sprint-8231-capacitor-app-readiness`
- `docs: define Capacitor app architecture and readiness plan`
- Sem push / sem merge / zero migrations
