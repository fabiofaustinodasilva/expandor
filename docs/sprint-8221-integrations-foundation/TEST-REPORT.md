# TEST-REPORT

Suite: `tests/Feature/Integrations/Sprint8221IntegrationsFoundationTest.php`

| # | Caso | Status |
|---|------|--------|
| 1 | Tenant ownership | PASS |
| 2 | Encrypted at rest | PASS |
| 3 | Masking UI | PASS |
| 4 | Elegível acessa config | PASS |
| 5 | Sem feature não configura | PASS |
| 6 | Backend bloqueia sem entitlement | PASS |
| 7 | Seller bloqueado | PASS |
| 8 | Cross-tenant | PASS |
| 9 | Teste válido → connected | PASS |
| 10 | Teste inválido → error + audit sem key | PASS |
| 11–14 | Resolver paths | PASS |
| 15 | Downgrade → Leaflet, keep credentials | PASS |
| 16 | Disconnect → Leaflet | PASS |
| 17 | MP intacto | PASS |
| 18 | Mapa continua Leaflet / sem key | PASS |
| 19 | Browser restriction = OK soft | PASS |

**13 tests / 70 assertions — PASS**

## Regressão

Filtro: Sprint8219, 82181, 8218, MapsModule, PilotSeller, MercadoPago relevantes — **77 passed (408 assertions)**
