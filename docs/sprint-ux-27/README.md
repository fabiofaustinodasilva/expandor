# Sprint UX 2.7 — Mapa Inteligente Comercial Expandor

## Entrega

Mapa operacional com camada comercial Expandor, sem trocar Leaflet/OpenStreetMap.

## Prints

- `mapa-comercial-rua.png` — visão rua + filtros + painel região + marcadores coloridos
- `mapa-comercial-satelite.png` — basemap satélite (Esri provisório)

## Testes

```
php artisan test
# 90 passed (493 assertions)
```

## Arquivos principais

- `public/js/map-provider.js` — `ExpandorMapProvider` + `ExpandorCommercialLayer`
- `public/js/operational-map.js` — filtros, clusters, painel, seleção de área
- `resources/views/maps/index.blade.php` — UI comercial
- `app/Domains/Maps/Enums/MapCommercialGroup.php`
- `app/Domains/Maps/Enums/MapMarkerColor.php`
- `app/Domains/Maps/DTOs/*`, `Repositories/MapRepository.php`, `Services/MapQueryService.php`
- `app/Http/Controllers/Api/V1/Maps/MapController.php` — `summary` na API
- `docs/maps-provider.md` — ponto de troca futuro Google/Mapbox
- `tests/Feature/Maps/MapsModuleTest.php`

## Limitações

1. Satélite atual = Esri World Imagery (provisório), não Google/Mapbox.
2. Oportunidade = heurística simples (ratio clientes/total), não motor de scoring.
3. Campanha por região = UI de seleção + modal “em breve” (sem criar campanha).
4. Clusters inteligentes mostram mix por status; spiderfy no zoom alto.
5. Seller mantém painel lateral de métricas oculto (simplicidade); filtros comerciais ficam flutuantes.
