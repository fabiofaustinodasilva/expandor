# CHANGELOG — Sprint 8.2.18

## Added

- UX de território na campanha: **Todos os setores** vs **Selecionar setores** (múltiplos), contador e busca.
- Endpoint `GET /campaigns/sectors-for-city?city_id=` (tenant-scoped).
- Filtro territorial no mapa: `campaign_id` → cidade da campanha + setores do pivot (vazio = cidade inteira).
- `Sprint8218SmartTerritoryCampaignTest` + docs em `docs/sprint-8218-smart-territory-campaign/`.

## Changed

- `MapRepository`: filtro por campanha deixa de ser “pontos com visita na campanha” e passa a ser território.
- `StoreCampaignRequest` / `UpdateCampaignRequest`: `territory_mode` + validação de setores.

## Not changed

- Schema / migrations estruturais (já existia `campaign_sectors`).
- Integrações IBGE/Correios/ViaCEP (só preparação documental).
- Redesign do mapa / geocoding automático.
