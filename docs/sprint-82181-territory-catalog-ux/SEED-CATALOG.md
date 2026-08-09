# SEED-CATALOG

## Fonte

IBGE Localidades API (captura offline versionada):
- `database/data/geo/states.json` — 27 UFs
- `database/data/geo/municipalities.json` — 5571 municípios

## Atualizar catálogo

```bash
php scripts/fetch-ibge-geo-catalog.php
php artisan db:seed --class=GeoCatalogSeeder
```

Não há chamada IBGE no formulário de campanha.

## Testes

Usam `Tests\Support\SeedsGeoCatalog::seedMiniGeoCatalog()` (GO/SP + 3 municípios) para velocidade.
