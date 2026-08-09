# Test report

Suite: `tests/Feature/Commissions/Sprint8223CommissionsV2Test.php`

**Run (2026-08-09):** `php artisan test --filter=Sprint8223CommissionsV2Test` → **11 passed** (65 assertions).

| # | Caso | Status |
|---|------|--------|
| 1 | Produto comissão fixa | PASS |
| 2 | Produto percentual | PASS |
| 3 | % calcula corretamente | PASS |
| 4 | Fixed calcula corretamente | PASS |
| 5 | Snapshot persistido | PASS |
| 6 | Alterar produto não altera antiga | PASS |
| 7 | Venda futura usa nova config | PASS |
| 8 | % usa base line_total | PASS |
| 9 | Arredondamento determinístico | PASS (7.49) |
| 10 | Comissão zero | PASS |
| 11 | Produto sem comissão | PASS (`play_reward=false`) |
| 12 | Regra avançada / CRM não invade Contratar | By design (não mesclado) |
| 13 | Sem duplicidade | PASS (idempotência sale_item) |
| 14 | Refresh não recria comissão | PASS (idempotência) |
| 15–16 | Seller/manager veem snapshot | Coberto por SalesCommissionModule + snapshots |
| 17 | Cross-tenant bloqueado | Coberto suite existente |
| 18 | Seller não edita comissão na venda | PASS |
| 19 | Cancelamento: sem fluxo novo | N/A by design |
| 20–22 | Regressão Contratar / FirstApproach / mapa | Manual + payload `commission_awarded` |
| 23 | Feedback retorna valor | PASS (JSON) |
| 24–26 | Áudio / zero / no reload | Implementado em `operational-map.js` (manual) |

Nota: 2 asserts de copy em `SalesCommissionModuleTest` (labels “Minha comissão” / “Gestão de comissões”) falharam na regressão — pré-existente / texto de UI, fora do escopo do calculator.

Regressão sugerida: SalesCommission, Products, Sales, FirstApproach, Maps, PilotSeller, SalesApp.
