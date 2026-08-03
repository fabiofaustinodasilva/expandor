# Sprint UX 2.9 — Inteligência Comercial do Gestor

## Entrega

Visão estratégica do gestor em **Resultados** + **Visão da equipe** no mapa. Seller inalterado.

## Prints

- `resultados-gestor.png` — KPIs, períodos Hoje/7d/30d, ranking
- `mapa-visao-equipe.png` — painel Visão da equipe + oportunidade regional

## Testes

```
92 passed (506 assertions)
```

## Arquivos

- `app/Http/Controllers/Web/Dashboard/DashboardController.php`
- `app/Domains/Analytics/Repositories/AnalyticsRepository.php`
- `app/Domains/Analytics/DTOs/DashboardMetricsDTO.php`
- `resources/views/dashboard/index.blade.php`
- `app/Http/Controllers/Web/Maps/MapController.php`
- `resources/views/maps/index.blade.php`
- `public/js/operational-map.js`
- `tests/Feature/Analytics/AnalyticsDashboardTest.php`
- `tests/Feature/Maps/MapsModuleTest.php`
- `docs/sprint-ux-29/*`

## Comportamento

1. **Resultados** — casas visitadas, novos pontos, interessados, contratos, conversão, ranking (Visitas | Interessados | Contratos), regiões com oportunidade.
2. **Período** — Hoje / 7 dias / 30 dias (default Hoje).
3. **Mapa gestor** — Visão da equipe (ativar), ranking do dia, foco por vendedor, legenda de oportunidade.
4. **Seller** — painel de equipe oculto; próxima casa / visita rápida preservados.
