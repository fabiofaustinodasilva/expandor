# PERFORMANCE

- Formulário: endpoint `GET /campaigns/sectors-for-city?city_id=` carrega **somente** setores da cidade selecionada (TenantScope).
- Busca local no picker quando a lista da cidade é grande (sem select HTML enorme).
- Mapa: 1 query `Campaign` + `sectors` ao aplicar `campaign_id`; sem N+1 por marker.
- Índices existentes (uniques city/sector) bastam; nenhum índice novo nesta sprint.
