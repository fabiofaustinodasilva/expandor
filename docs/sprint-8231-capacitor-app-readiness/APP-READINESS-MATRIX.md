# APP-READINESS-MATRIX

| Função | WEB | API | OFFLINE | CAPACITOR | BLOCKER | ACTION |
|--------|-----|-----|---------|-----------|---------|--------|
| Login | READY | READY (token) | n/a | BLOCKER | cookie WebView; PAT sem device | 8.2.33 Bearer + Keychain |
| Password reset | READY | MISSING | n/a | PARTIAL | abre browser | 8.2.33 deep link doc; API opcional |
| Map | READY | PARTIAL markers | MISSING tiles | BLOCKER | CDN Leaflet/Tailwind | 8.2.32 vendor + 8.2.34 |
| GPS | READY browser | n/a | n/a | BLOCKER | WebView geo frágil | LocationService 8.2.34 |
| Point create | READY web JSON | MISSING | PARTIAL fila CSRF | BLOCKER | sem API | 8.2.35–36 |
| Visit | READY | PARTIAL mobile POST | PARTIAL fila | BLOCKER | mapa usa CSRF | API unificada |
| Sale | READY no sheet | MISSING dedicado | MISSING | BLOCKER | idempotency | 8.2.36 |
| Follow-up | READY Blade | MISSING complete | MISSING | — | API | 8.2.35 |
| Agenda | READY | MISSING list | MISSING | — | API | 8.2.35 |
| Clients | READY lista 8.2.30.1 | MISSING | MISSING | — | API | depois do mapa |
| Commission | READY | MISSING | estimativa only | — | reward só pós-sync | 8.2.36 |
| Products | READY apresentar | MISSING | cache futuro | — | HTML deck | 8.2.35 |
| Academy | READY (layout.app no Mais) | MISSING | n/a | — | shell errado | usar sales-app.training |
| Profile | READY | MISSING | n/a | — | API | baixo |
| Presence | READY web | MISSING | n/a | — | ping 2 min | 8.2.33 |
| Single session | READY web | **GAP** PAT | n/a | BLOCKER | tokens paralelos | 8.2.33 |

Push: DEFERRED. Camera: só se upload seller exigir (hoje mapa não depende).
