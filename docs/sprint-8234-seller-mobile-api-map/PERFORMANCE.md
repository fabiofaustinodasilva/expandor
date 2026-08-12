# Performance

- Markers: eager load address/city/residents no `MapRepository` (limite 80 na busca textual).
- Point list/detail: eager load de `CustomerQueryService`.
- Agenda: visit.property.address + campaign.
- Commissions: paginate do repositório.
- Products: query única do catálogo ativo.
- App: debounce 400 ms na busca; bbox no mapa para não pedir a cidade inteira.

Teste estrutural: markers com 8 pontos < 25 queries.
