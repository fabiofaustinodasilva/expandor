# ROADMAP

## P0 — Blockers para campo (aprovar antes de codar)

1. **Timezone** `America/Sao_Paulo` + presenter de datas (Equipe + visitas + comissões).  
2. Validação manual horários Equipe vs relógio BR.  
3. Smoke campo: login → mapa → ponto → visita → venda → reward.

## P1 — Polish antes do app

1. House pin + legenda alinhada.  
2. Glossário copy (ponto/morador/cliente) no mapa.  
3. Unificar CTAs mapa → tokens brand.  
4. Remover/substituir Tailwind CDN por build (ou pelo menos documentar risco).  
5. Empty/loading/error patterns mínimos.  
6. Equipe: labels “último login” vs “última atividade” explícitos.

## P2 — App readiness

1. Session cookie WebView.  
2. Bundle Leaflet/Lucide local.  
3. Touch `:active` states.  
4. Deep links auth.  
5. Capacitor spike (sprint dedicada).

## P3 — Futuro

1. Totais financeiros (vendido/comissão/pendente/pago).  
2. Timezone por empresa.  
3. Design system documentado em Story-like pages.  
4. Skeletons amplos.  
5. Unificar equipe-no-mapa vs hub Equipe.

## Slices sugeridos (PRs futuros)

| Slice | Escopo | Risco |
|-------|--------|-------|
| 8.2.27.1 | Timezone only | Baixo-médio |
| 8.2.27.2 | Map house pins | Médio (JS/CSS) |
| 8.2.27.3 | Copy glossary | Baixo |
| 8.2.27.4 | Brand CTAs mapa | Baixo |
| 8.2.28+ | Capacitor | Alto |

**Esta sprint para em documentação.**
