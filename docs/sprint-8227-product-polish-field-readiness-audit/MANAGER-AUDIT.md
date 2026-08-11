# MANAGER-AUDIT

## Superfície

Mapa empresa: busca, filtros, legenda, métricas, equipe overlay, região, clusters.

## Consistência com Seller

| Aspecto | Status |
|---------|--------|
| Basemap / markers / clusters | Mesmo motor — bom |
| CTAs / cores chrome | Mesmo sky — bom entre si, ruim vs CRM |
| Densidade UI | Maior (filtros + painel) — risco congestão 1366 |
| Equipe | Depende de timezone (P0) |

## Problemas

1. Painel direito / métricas competem com mapa em notebook 1366.
2. Filtros comerciais + legenda: carga cognitiva para gerente novo.
3. “Visão da equipe” vs presença Equipe (`/operacoes/equipe`) — dois conceitos próximos.
4. Região / campanha por desenho: ainda “em breve” em parte do fluxo.

## Recomendação

P1: alinhar tokens de cor ao design system.  
P1: timezone Equipe.  
P2: simplificar default de filtros (menos checkboxes abertos).  
P3: unificar “equipe no mapa” vs hub Equipe.
