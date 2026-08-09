# README — Sprint 8.2.18.1

Catálogo global UF/municípios + UX de campanha sem CRUD prévio de Cidades/Setores.

## Branch

`feature/sprint-8218-1-territory-catalog-ux`

## Entrega (A+B+C)

| Fase | O quê |
|------|--------|
| A | `geo_states` / `geo_municipalities` + seed IBGE versionado + `cities.geo_municipality_id` |
| B | Campanha: Estado → Cidade (catálogo) → Toda a cidade |
| C | Áreas específicas + criar área inline (Sector tenant) |

**Não incluso:** provider de bairros (D), Central de Integrações.

## Fluxo

1. Usuário escolhe UF + município do catálogo (local).
2. `TerritoryService::upsertCityFromCatalog` materializa `City` no tenant.
3. Toda a cidade → `campaign_sectors` vazio.
4. Áreas → setores da City; `+ Criar área` via POST inline.
