# AUDIT — Sprint 8.2.18 Smart Territory Campaign

## Respostas obrigatórias

| # | Pergunta | Resposta |
|---|----------|----------|
| 1 | Como cidades são armazenadas? | Tabela `cities`: `company_id`, `name`, `state` (UF 2 chars), `ibge_code` nullable, `active` |
| 2 | Cidade pertence à empresa? | **Sim** — `BelongsToTenant` + unique `(company_id, name, state)` |
| 3 | Existe UF/estado? | **Sim** — coluna `state` (não há tabela `states`) |
| 4 | Código IBGE? | **Sim** — `ibge_code` nullable (sem preenchimento automático) |
| 5 | Como setores são armazenados? | Tabela `sectors`: `company_id`, `city_id`, `name`, `description`, `active` |
| 6 | Sector pertence a City? | **Sim** — FK `city_id` |
| 7 | Sector pertence à Company? | **Sim** — `company_id` + TenantScope |
| 8 | Mesmo nome em cidades diferentes? | **Sim** — unique `(company_id, city_id, name)` |
| 9 | Campaign ↔ City? | FK obrigatória `campaigns.city_id` |
| 10 | Campaign ↔ Sector? | Pivot `campaign_sectors` (N:N) — **não** há `campaigns.sector_id` |
| 11 | Pivot `campaign_sector`? | **Sim** — `campaign_sectors` (PK composta) |
| 12 | Um ou vários setores? | **Vários** já suportados (`sector_ids[]`) |
| 13 | Pontos ↔ City/Sector? | Via `addresses` (`city_id` required, `sector_id` nullable); `properties.address_id` |
| 14 | GPS define setor automaticamente? | **Não** — FirstApproach/`MapPointController`: `city_id` required, `sector_id` opcional |
| 15 | Mapa filtra como? | Query params city/sector/campaign; campaign hoje = pontos **com visita** na campanha (gap territorial) |
| 16 | Policies? | `CityPolicy`, `SectorPolicy`, `CampaignPolicy`, `PropertyPolicy` + permissões `*.view`/`*.manage` |
| 17 | Risco cross-tenant? | Mitigado por TenantScope + validação setor∈cidade da campanha; testes obrigatórios |
| 18 | Migrations necessárias? | **Nenhuma estrutural** para multi-setor / Todos |
| 19 | “Todos os setores” sem migration? | **Sim** — pivot `campaign_sectors` **vazio** = cidade inteira |
| 20 | Importação futura? | `ibge_code` + `TerritoryService::upsertSector` + Central de Integrações (provider) |

## Modelo desejado vs atual

Já alinhado: Estado(UF em City) → Cidade → Setor → Pontos; Campanha → Cidade → Todos (pivot vazio) OU N setores.

## Gaps desta sprint (sem schema novo)

1. UX explícita: rádio **Todos os setores** vs **Selecionar setores**
2. Mapa com filtro de campanha deve respeitar **território** da campanha (cidade + setores), não só “tem visita”
3. Contador / busca de setores quando lista grande
4. Documentar preparação para providers externos

## Migration estrutural

**Não necessária.** Prosseguir implementação.
