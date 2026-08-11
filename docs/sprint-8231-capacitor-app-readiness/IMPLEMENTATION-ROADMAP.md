# IMPLEMENTATION-ROADMAP

Ajustado à auditoria (não instalar Capacitor em 8.2.31).

| Slice | Foco | Não fazer |
|-------|------|-----------|
| **8.2.31** (esta) | Audit + ADR | Capacitor, APK, sync |
| **8.2.32** | Vendor Leaflet/Lucide; iniciar build Tailwind local; `viewport-fit=cover` operacional | Trocar regra de mapa |
| **8.2.33** | Capacitor bootstrap vazio + Bearer device + fechar gap `session_version` + presence ping API + CORS allowlist | Offline |
| **8.2.34** | LocationService + permissões + back button / status bar | Background GPS |
| **8.2.35** | SQLite + fila + APIs missing (ponto, follow-up, agenda, catálogo) | Conflito complexo |
| **8.2.36** | Sync + idempotency + comissão só no server | Recalc offline |
| **8.2.37** | Android internal track QA | Produção Play |
| **8.2.38** | iOS TestFlight | Produção App Store |

Updates: binário para plugins/shell; API/HTML hospedado para copy/features. Sem OTA obscuro no MVP.

Versionar: `1.0.0` + build number; header `X-App-Version` futuro.
