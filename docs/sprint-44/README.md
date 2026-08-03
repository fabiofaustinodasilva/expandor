# Sprint 4.4 — Dashboard Comercial Gestor

## Objetivo

Evoluir **Resultados** para painel de gestão comercial (funil, ranking, produtividade, regiões, alertas).

## Escopo entregue

| Bloco | Detalhe |
|---|---|
| Funil | Pontos → Visitas → Interessados → Contratos (+ % entre etapas) |
| Ranking | Ordenação contratos → interessados → conversão → visitas; coluna Conversão %; Abrir mapa `?user_id=` |
| Produtividade | Ativos, média visitas/contratos, melhor vendedor |
| Regiões | Casas (`COUNT DISTINCT property_id`), visitas, interessados, contratos, conversão, mapa `?sector_id=` |
| Alertas | Sem visita hoje · muitos retornos (≥10) · região baixa conversão (≥10 visitas e &lt;5%) |
| Permissões | Manager/Admin/Supervisor: visão completa · Seller: só próprios (sem ranking/regiões da equipe) |
| Comissões | Placeholder Sprint 5.0 |
| Filtros | + Vendedor (gestor); mantém período/cidade/setor |

## Arquivos

- `AnalyticsRepository.php`
- `DashboardMetricsService.php`
- `DashboardMetricsDTO.php`
- `DashboardController.php`
- `resources/views/dashboard/index.blade.php`
- `public/js/operational-map.js` (deep-link `user_id` / `sector_id`)
- `tests/Feature/Analytics/AnalyticsDashboardTest.php`

Sem migration.

## Prints

1. `resultados-funil.png`
2. `resultados-ranking.png`
3. `resultados-alertas.png`
4. `resultados-seller.png` (visão restrita)

## Testes

```
php artisan test --filter=AnalyticsDashboardTest
php artisan test
```

Validação: **6** testes Analytics (33 assertions) + suite completa **150 passed** (814 assertions).
