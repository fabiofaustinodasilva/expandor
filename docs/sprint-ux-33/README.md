# Sprint UX 3.3 — Mapa Seller totalmente limpo

## Objetivo

Remover elementos visuais que atrapalham a visão do mapa no modo vendedor. Sem alterar banco, APIs, permissões ou regras de negócio.

## Prints

- `antes-seller.png` — estado anterior (busca + Filtros/Camadas/Legenda + zoom + créditos OSM)
- `depois-seller.png` — mapa limpo (~90%): Nova oportunidade, ☰ Camadas, Rua/Satélite, Próxima casa
- `depois-seller-camadas.png` — painel temporário **Mostrar** ao tocar em ☰ Camadas
- `depois-manager.png` — manager mantém filtros/créditos/chrome completo (referência)

## Mudanças

1. **Zoom Leaflet** — botões `+`/`−` removidos no Seller mobile; gestos (pinch / duplo toque) ativos. Desktop Seller (≥901px) mantém zoom.
2. **Créditos** — `Leaflet | © OpenStreetMap` ocultos no Seller (`attributionControl: false` + CSS). Manager preserva.
3. **Camadas** — checkboxes Clientes/Interessados/Visitados/Novos pontos fora da tela principal; só **☰ Camadas** abre painel temporário (fecha com X, Escape ou clique no mapa).
4. **Header limpo** — busca, Filtros e Legenda fora do Seller; filtros stub ocultos para o JS.
5. **Manager** — filtros comerciais, legenda, busca e créditos intactos.

## Arquivos

- `resources/views/maps/index.blade.php`
- `public/js/operational-map.js` (`?v=36`)
- `public/js/map-provider.js` (`?v=2`, `hideAttribution`)
- `tests/Feature/Maps/MapsModuleTest.php`
- `docs/sprint-ux-33/*`

## Testes

```
MapsModuleTest + PilotSellerUxTest: 12 passed (89 assertions)
Suite completa: 96 passed (552 assertions)
```
