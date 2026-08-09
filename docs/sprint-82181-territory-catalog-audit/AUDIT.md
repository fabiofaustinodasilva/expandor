# AUDIT — Sprint 8.2.18.1 (Adendo)

**Status:** DIAGNÓSTICO ONLY — sem implementação, sem migration, sem API, sem push.  
**Base:** `feature/sprint-8218-smart-territory-campaign` (`f53fb3a`)

## Problema de produto validado

Hoje o fluxo obriga: Cidades CRUD → Setores CRUD → Campanhas.  
Isso **não** é aceitável como fluxo principal. A tela Nova Campanha deve montar o território (Estado → Cidade → Toda cidade | Bairros/áreas), com áreas personalizadas tenant-specific.

## Respostas da auditoria (1–20)

### 1. Por que City é manual hoje?
Modelo operacional **tenant-first**: `cities.company_id` + unique `(company_id, name, state)`. Criado para a empresa delimitar onde opera, sem catálogo nacional. CRUD em `/cities`. Sem seed nacional.

### 2. Por que Sector é manual?
Território de campo **customizável** (Centro, Zona Rural, GO-221). Unique `(company_id, city_id, name)`. Não há catálogo de bairros. Intencional para ISP/campo — e correto para áreas personalizadas; incorreto como **pré-requisito** da campanha.

### 3. Dependências das tabelas

```
cities.id
  ├── sectors.city_id (cascade)
  ├── addresses.city_id (cascade) → properties
  └── campaigns.city_id (cascade)

sectors.id
  ├── addresses.sector_id? (nullOnDelete)
  └── campaign_sectors.sector_id (cascade)
```

**Não** transformar `City` em global sem ponte: quebraria tenancy e FKs.

### 4. Melhor desenho: catálogo global ≠ território da empresa

| Camada | Conteúdo | Escopo |
|--------|----------|--------|
| **Catálogo Expandor** | UF, municípios (IBGE), bairros oficiais *quando houver* | Global / plataforma |
| **Território empresa** | `cities`, `sectors` (ops + personalizados), campanhas, pontos | Tenant |

Fluxo: escolher no catálogo → **materializar** `City`/`Sector` tenant via upsert (já existe `TerritoryService::upsertCity/upsertSector`) → campanha aponta para IDs tenant (como hoje).

**Não** converter `cities` em tabela global.

### 5. Fonte recomendada para UF/municípios
**IBGE Localidades** (`servicodados.ibge.gov.br/api/v1/localidades`) — oficial, gratuita, estável para estados/municípios (~5570).  
Preferir **seed/sync offline** no Expandor (tabelas `geo_*`) em vez de chamar IBGE a cada abertura de campanha.

### 6. Opções reais para bairros

| Fonte | Lista bairros por cidade? | Adequação |
|-------|---------------------------|-----------|
| IBGE Localidades API | **Não** (município/distrito/subdistrito ≠ bairro de rua) | Ruim para UX “Centro / Vila X” |
| IBGE Censo/DTB bairros | Parcial; forte em capitais; fraco em cidades pequenas | Cobertura irregular |
| ViaCEP / BrasilAPI | CEP → 1 bairro; **não** lista todos os bairros do município | Ruim como picker |
| Providers comerciais CEP/geo | Variável; custo/licença | Avaliar na Central de Integrações |
| **Área personalizada (tenant)** | Sempre disponível | **Obrigatório** no MVP |

**Conclusão:** não escolher provider de bairro por suposição. MVP deve funcionar **sem** lista oficial.

### 7. Cobertura em cidades pequenas (ex.: Bom Jardim de Goiás)
Município IBGE: **alta**.  
Bairros oficiais utilizáveis no picker: **baixa/incerta**.  
Produto deve: Toda a cidade OK; se sem bairros → “Criar área personalizada”; **nunca** bloquear campanha.

### 8. Estratégia áreas personalizadas
Manter `sectors` tenant. Botão “+ Adicionar área personalizada” na própria campanha → `upsertSector` na `City` materializada → incluir em `campaign_sectors`. CRUD `/sectors` vira ferramenta avançada.

### 9. Impacto no schema (proposto — não executar)

**Novo (global):**
- `geo_states` (uf, name, ibge_id)
- `geo_municipalities` (geo_state_id, name, ibge_code unique)
- opcional futuro: `geo_neighborhoods` (municipality_id, name, source, external_id)

**Tenant (compat):**
- `cities.geo_municipality_id` nullable
- `sectors.source` enum/`custom|catalog` + `geo_neighborhood_id` nullable (opcional)
- **`campaign_sectors` inalterado**

### 10. Migrations necessárias
Sim, **estruturais** para catálogo + FKs nullable de ponte.  
Conforme regra do adendo: **NÃO criar agora** — aguardar aprovação.

### 11. Compatibilidade com dados existentes
- Cidades/setores atuais permanecem
- Campanhas com `city_id` / pivot continuam
- Backfill opcional: match `cities.ibge_code` ou `(name, state)` → `geo_municipalities`
- Sem delete; sem renomear IDs

### 12. Impacto em Campaign
UX: Estado → Município (catálogo) → Toda cidade | Escolher áreas.  
Persistência: continua `campaigns.city_id` + `campaign_sectors`.  
“Toda a cidade” = pivot vazio (já é a semântica 8.2.18).

### 13. Impacto no mapa
Filtro territorial 8.2.18 (cidade ± setores) permanece.  
Pontos seguem `addresses.city_id/sector_id` tenant.  
Sem redesign.

### 14. Impacto no seller
Sem mudança de regra de território se materialização for transparente.  
First-approach ainda usa `city_id` tenant (pode pré-selecionar pela campanha).  
GPS→bairro continua futuro (Central de Integrações).

### 15. Tenancy
Catálogo global = leitura para todos.  
`cities`/`sectors`/campanhas/pontos = tenant.  
Empresa A nunca vê setores personalizados de B.  
Materialização só no `company_id` do contexto.

### 16. Cache / fallback
1. Catálogo local `geo_*` (fonte da verdade na app)
2. Sync job periódico IBGE (plataforma)
3. Se sync falhar: app continua com último snapshot
4. Bairros provider: timeout → UI “sem lista” + área personalizada
5. **Nunca** dependência síncrona externa no GET do form de campanha

### 17. Custos futuros
- IBGE Localidades / DTB: **gratuito** (uso de dados públicos)
- CEP/geo comerciais: avaliar preço/SLA/licença na Central
- Storage: ~5–10k municípios + N bairros se importados — baixo

### 18. Preparação Central de Integrações
```
TerritoryCatalogProvider (interface)
  ├── IbgeLocalidadesProvider   // UF + municípios (recomendado fase 1)
  ├── NeighborhoodProvider?     // pluggable; soft-fail
  └── ManualCompanyProvider     // setores personalizados (já existe)
```
Admin Plataforma → Integrações → Território → Provider + última sync.  
Domínio **não** acopla a URL/API key hardcoded.

### 19. Proposta final de UX (campanha)

```
Estado [ Goiás ▼ ]
Cidade [ Bom Jardim de Goiás ▼ ]   // busca no catálogo geo_*

Onde trabalhar?
(●) Toda a cidade
( ) Escolher bairros / áreas

Se escolher:
  [lista catálogo se houver]
  [setores já existentes da empresa na cidade]
  + Adicionar área personalizada

[Criar campanha]  // nunca bloqueado por falta de bairro/API
```

Menus Cidades/Setores: **avançado/admin**, não pré-requisito.

### 20. Plano por etapas (após aprovação)

| Etapa | Escopo | Migration? |
|-------|--------|------------|
| **A** | `geo_states` + `geo_municipalities` + seed IBGE offline; ponte `cities.geo_municipality_id` | Sim |
| **B** | UX campanha Estado→Cidade→Toda cidade; upsert City no store | Sim (A) |
| **C** | “+ Área personalizada” inline; opcional multi-setor já existente | Não estrutural além de A |
| **D** | Provider bairros pluggable + soft-fail (Central) | Opcional `geo_neighborhoods` |
| **E** | Reposicionar CRUD Cidades/Setores como admin | Docs/UI only |

## Decisão pedida

**Aprovar Etapas A+B+C** (desbloqueia produto sem provider de bairro)  
ou  
**Aprovar só A** (fundação)  
ou  
**Ajustar desenho** antes de qualquer migration.

## Riscos se avançar sem catálogo
Continuar melhorando só o form 8.2.18 **não** resolve o pré-requisito manual Cidades/Setores.
