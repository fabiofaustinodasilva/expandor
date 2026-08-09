# MIGRATION-NOTES

1. `2026_08_09_120001_create_geo_catalog_tables`
2. `2026_08_09_120002_add_geo_municipality_id_to_cities_table`

## Deploy

```bash
php artisan migrate
php artisan db:seed --class=GeoCatalogSeeder
```

`DatabaseSeeder` já chama `GeoCatalogSeeder`.

## Rollback

`migrate:rollback` remove FK e tabelas geo. Cities voltam sem ponte (drop column).

## Compatibilidade

Cidades existentes ficam com `geo_municipality_id = null`. Campanhas antigas editáveis via `city_id`.
