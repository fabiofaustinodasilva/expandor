# CHANGELOG — Sprint 8.2.10

## Alterado

- `app/Domains/Maps/Enums/MapMarkerColor.php` — cor por status; `markForStatus`; `SLATE`
- `app/Domains/Maps/Enums/MapCommercialGroup.php` — `legend()` com Retorno / Sem interesse
- `public/js/map-provider.js` (v=3) — `colorOf` / `markOf`; clusters sem emoji
- `public/js/operational-map.js` (v=45) — marca no pin; toggle legenda; seller vê legenda
- `resources/views/maps/index.blade.php` — legenda collapsible + CSS posição

## Não alterado

Banco, PropertyStatus, VisitStatus, filtro `visited`, fluxo campo 8.2.9, auth/tenancy/billing.
