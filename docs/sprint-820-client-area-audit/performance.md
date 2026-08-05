# Performance — Sinais observados (análise estática)

> Sem profiling em produção nesta sprint. Achados por leitura de código.

## Dashboard (quente)

`DashboardController` em um único request:

1. `DashboardService::summary()`
2. `DashboardMetricsService::metrics()` (funil, produtividade, alertas)
3. `OnboardingService::status`
4. `SaasOnboardingService` (activation card + workspace ready)
5. `ActivationIntelligenceService::snapshot`
6. `TerritoryRepository` cities + sectors
7. `AnalyticsRepository::filterableSellers`

**Risco:** página inicial pesada, especialmente com onboarding/ativação ativos.  
**Sugestão 8.2.1:** deferred/lazy partials para ativação; cache curto de métricas.

## Consultas / N+1 (candidatos)

| Área | Sinal | Notas |
|------|-------|-------|
| Map markers API | Markers por campanha/área | Validar eager load properties/status |
| CRM Kanban | Opportunities + stages | Risco N+1 se não eager |
| Equipe | Users + role + overrides | `loadMissing` já usado em More/Dashboard |
| Campaigns index | Lista + counts visitas | Verificar withCount |
| Customers index | Query agregada | Revisar `CustomerQueryService` |
| Commissions index | Lista + relations | Revisar eager |
| Training indexes | Categories/contents | Baixo volume típico |

Não há `with()` óbvio em `DashboardMetricsService` grep — métricas tendem a ser agregações SQL (melhor). Ainda assim, alertas por setor podem iterar collections.

## Widgets / componentes carregados sem necessidade

- Activation + setup cards no dashboard mesmo quando usuário só quer KPIs.
- Cities/sectors sempre carregados para filtros (ok se poucos; problema se território enorme).
- Layouts incluem Lucide CDN no operational — custo fixo aceitável.

## Controllers / Services grandes (manutenibilidade + risco)

| Artefato | Observação |
|----------|------------|
| `routes/web.php` | Arquivo único muito grande (~todo o produto) |
| `TeamController` | Muitas ações (CRUD + permissões + senha) |
| `SaasOnboardingController` | Muitos steps |
| `MapController` + related | Lógica de campo concentrada |
| `MoreController` | Menu dinâmico — ok, mas duplica lógica de nav |

## Ações duplicadas (custo duplo)

- Visitas criadas via Map, Campaigns, SalesApp, Mobile API — 4 entradas para o mesmo domínio (correto para UX, exige service único — validar que todos usam `VisitService`/Actions).

## Recomendações (sem implementar)

1. Instrumentar Telescope/Debugbar em homolog no `/dashboard` e `/map`.  
2. Separar payload de ativação do de métricas.  
3. Paginar sempre listas CRM/properties/commissions.  
4. Revisar endpoints mobile `pending-sync` sob carga.
