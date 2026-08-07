# Performance — Lista priorizada

> Análise estática (8.2.0) + priorização para implementação 8.2.2+.  
> Sem profiling de produção nesta sprint.

## P0 — Home (Dashboard)

| Item | Problema | Ação proposta | Impacto |
|------|----------|---------------|---------|
| Payload misturado | Métricas + onboarding + activation + territory + sellers num request | Separar: KPIs first; ativação lazy/partial | Alto |
| Filtros território sempre | Cities/sectors carregam sempre | Lazy ao abrir “Filtros” | Médio |
| Widgets a mais | Funil/alertas densos na home | Mover p/ Relatórios | Alto (UX+perf) |

## P1 — Mapas e campo

| Item | Problema | Ação proposta |
|------|----------|---------------|
| Markers | Possível over-fetch / N+1 | Eager load + bounding box; medir API |
| Visitas multi-entry | 4 UIs → mesmo service (ok) | Garantir sem queries extras por UI |

## P2 — Listas

| Área | Ação |
|------|------|
| CRM Kanban | Eager `stage`, `owner`, `lead` |
| Campanhas | `withCount` visitas |
| Clientes / Pontos | Paginar; índices já existentes |
| Comissões | Eager user/visit |
| Equipe | Já usa `loadMissing` — validar em lista grande |

## P3 — Estrutural

| Item | Nota |
|------|------|
| `routes/web.php` monolítico | Split por domínio (devex, não runtime) |
| Lucide CDN | Aceitável; eventualmente bundle Vite |
| Mobile `pending-sync` | Load test antes de campanha grande |

## Ordem de ataque recomendada (8.2.2)

1. Dashboard slim (P0) — maior ganho percebido.  
2. Eager loads CRM/Campanhas (P2) — risco baixo.  
3. Map markers measure (P1) — precisa baseline.  
4. Split routes file (P3) — cosmético técnico.

## O que não fazer

- Cache agressivo que atrase visitas em tempo real.  
- Reescrever mapa.  
- Adicionar Redis “porque sim” sem métrica.
