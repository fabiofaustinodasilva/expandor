# RESPONSIVE-AUDIT

Auditoria por código/CSS (sem device lab nesta sprint).

## Breakpoints críticos

| Largura | Mapa | Deck | Agenda/Comissão | Risco |
|---------|------|------|-----------------|-------|
| 320 | toolbar apertada; Apresentar+GPS | chrome 3 botões | tabelas scroll | Alto |
| 360–390 | tipografia reduzida | OK com safe-area | OK | Médio |
| 430 | form actions sticky | OK | OK | Baixo |
| 768+ | drawer lateral | max-width deck | desktop | Baixo |

## Pontos conhecidos (sprints 8.2.7–13)

- Safe-area no deck (`env(safe-area-inset-*)`)  
- Modal ponto `max-height` / sticky actions  
- Field-seller CSS esconde chrome admin  
- Bottom Sales App + teclado: risco de CTA coberto (não medido em lab)

## Registrar para 8.2.16 QA device

- [ ] 320: Voltar / Detalhes / Contratar sem overlap  
- [ ] Teclado iOS no FirstApproach  
- [ ] Bottom-sheet Detalhes + swipe  
- [ ] Agenda complete modal + sale fields
