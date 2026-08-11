# PERFORMANCE-AUDIT

Riscos reais (sem otimização prematura):

| Risco | Evidência | Severidade |
|-------|-----------|------------|
| Tailwind CDN runtime | `layouts/operational.blade.php` | Alta (rede + warning) |
| Muitos markers + cluster | MarkerCluster + divIcons | Média em cidades grandes |
| `loadMarkers` a cada moveend | debounce 400ms | Média |
| N+1 em listagens CRM | Auditar sob carga | Média |
| Imagens/logo branding | storage public | Baixa |
| Cache bust `?v=57` map JS | Ok | Baixa |
| Áudio reward | wav local | Baixa |

## Não fazer nesta sprint

Redis, rewrite de queries, virtualização de markers — só se P0 de campo.
