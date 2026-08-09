# CHANGELOG — 8.2.18.1

## Added
- Tabelas globais `geo_states`, `geo_municipalities`
- JSON versionado `database/data/geo/*.json` (IBGE Localidades)
- `GeoCatalogSeeder` + script `scripts/fetch-ibge-geo-catalog.php`
- `cities.geo_municipality_id` (nullable) + unique `(company_id, geo_municipality_id)`
- `TerritoryService::upsertCityFromCatalog`
- Endpoints: `campaigns/municipalities`, `areas-for-municipality`, `areas` (store)
- UX campanha Estado/Cidade/Toda a cidade/Áreas + criar área inline
- `Sprint82181TerritoryCatalogUxTest`

## Changed
- Store campanha exige `geo_municipality_id` (materializa City)
- Update aceita `geo_municipality_id` ou `city_id` legado
- Nomes reservados (“Todos…”) bloqueados na criação de área

## Not changed
- Estrutura de `campaign_sectors`
- CRUD Cidades/Setores (permanece admin)
- Provider de bairros / Central de Integrações
