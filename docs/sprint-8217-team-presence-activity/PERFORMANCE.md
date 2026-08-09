# PERFORMANCE

## Estratégia (evitar N+1)

1. Membros comerciais: 1 query + eager role/campaigns
2. Produtividade do dia: 1 agregado (`DashboardMetricsService`)
3. Presença: lê `users.last_seen_at` já eager no member (sem query `sessions`)
4. Middleware: no máx. 1 UPDATE / usuário / 2 min
5. Últimas visitas: `MAX(id) GROUP BY user_id` + eager property/residents
6. Últimas vendas: `MAX(sales.id) GROUP BY visit.user_id` + eager items/resident
7. Detalhe (1 vendedor): timeline limitada (20) + conexões (20)

## Janela online

`TeamPresenceActivityService::ONLINE_WINDOW_MINUTES = 5`  
`TeamPresenceActivityService::PRESENCE_TOUCH_MINUTES = 2`
