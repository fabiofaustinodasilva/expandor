# CHANGELOG — Sprint 8.2.8

## Alterado

- `resources/views/maps/index.blade.php`
  - FAB **Minha localização** (`#btn-recenter-location`).
  - Labels mobile: Nome/responsável, Telefone/WhatsApp, Observação curta, Situação / interesse.
  - Grid `field-seller-outcome-grid` nos outcomes.
  - CSS de posicionamento do cluster basemap + recenter.
  - Cache bust `operational-map.js?v=43`.

- `public/js/operational-map.js`
  - `recenterOnMyLocation()` — GPS + center, sem `openPointModal`.
  - Copy GPS negado / Localização obtida / Ponto registrado / erro de save.
  - Seller create: pula `openPostCreateAdjust` + fecha drawer; centra no ponto.
  - Label GPS amigável para field seller.

- `tests/Feature/Release/Sprint827MapUxSimplificationTest.php` — mensagem GPS alinhada.
- `tests/Feature/Release/Sprint828FieldSalesMobileFlowTest.php` — novo.

## Não alterado

- Banco, migrations, tenancy, auth, Policies/Gates, billing, Mercado Pago.
- Controllers/services/APIs de ponto e first-approach.
- Enums `VisitStatus` / `PropertyStatus` (sem novos status).
- Agrupamento de cores comerciais (sem domínio visual novo).

## Backend necessário — motivo (não implementado)

Separar visualmente no mapa **Retornar depois** vs **Não interessado** exigiria ajuste de grupos/cores no backend ou contrato do marker payload — fora do escopo UI-only.

## Testes

| Suite | Resultado |
|-------|-----------|
| Sprint828 + Sprint827 | **9 passed** |
| + MapsModule + FirstApproach + PilotSeller + SalesApp | **31 passed** |
| `artisan test` completo | **465 passed / 4 failed** |

Falhas do suite completo (pré-existentes / fora do mapa): comissões, team hub, visit history (“Rota”).
