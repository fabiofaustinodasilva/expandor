# PERFORMANCE

## Estratégia (evitar N+1)

1. Membros comerciais: 1 query + eager role/campaigns
2. Produtividade do dia: 1 agregado (`DashboardMetricsService`)
3. Presença: 1 `GROUP BY user_id` em `sessions`
4. Últimas visitas: `MAX(id) GROUP BY user_id` + eager property/residents
5. Últimas vendas: `MAX(sales.id) GROUP BY visit.user_id` + eager items/resident
6. Detalhe (1 vendedor): timeline limitada (20) + conexões (20)

## Janela online

`TeamPresenceActivityService::ONLINE_WINDOW_MINUTES = 5`
