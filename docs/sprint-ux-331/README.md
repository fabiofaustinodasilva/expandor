# Sprint UX 3.3.1 — Correção visual mapa Seller

## Problema

Após UX 3.3 ainda apareciam no Seller: zoom +/−, créditos Leaflet/OSM e bloco Mostrar (camadas).

## Correção

Seller (definitivo):
- Zoom Leaflet **nunca** adicionado (sem exceção desktop)
- Attribution desligada + CSS `!important` + remoção DOM
- Bloco Mostrar / Camadas / Legenda **sempre ocultos** (`hidden` + CSS via `body.field-seller` e `#map-page[data-is-field-seller="1"]`)
- Botão ☰ Camadas removido do header

Manager/Admin: inalterado.

## Arquivos

- `resources/views/maps/index.blade.php`
- `resources/views/layouts/operational.blade.php` (`loadMissing('role')`)
- `public/js/operational-map.js` (`?v=37`)
- `app/Http/Controllers/Web/Maps/MapController.php` (`loadMissing('role')`)
- `tests/Feature/Maps/MapsModuleTest.php`
- `docs/sprint-ux-331/*`

## Testes

```
MapsModuleTest + PilotSellerUxTest: 12 passed
Suite completa: 96 passed (552 assertions)
```

## Print

- `seller-mapa-limpo.png` — sem zoom, sem créditos, sem Mostrar; só mapa, marcadores, Nova oportunidade, Rua/Satélite, Próxima casa
