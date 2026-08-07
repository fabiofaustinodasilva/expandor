# Campanhas — auditoria UX

**Views:** `campaigns/index.blade.php` (operational); `create`/`edit`/`_form` (app)  
**Rotas:** `campaigns.*` → `/campaigns`

## Clareza imediata

Lista de campanhas com status e ações é compreensível. O salto
**índice (rail) → create/edit (sidebar clássica)** quebra continuidade
(**P1**, quase P0 em sensação de produto).

## Checklist das 20 perguntas

| # | Achado | Sev |
|---|--------|-----|
| 1 Clareza | Alta na listagem | — |
| 2 Excesso | Coluna de ações com 4–5 botões | P1 |
| 3 Escondido | Ações somem/wrapam no mobile | P1 |
| 4 Duplicação | Campanhas também no Sales App | P2 |
| 5 Botão | Ativar/Pausar/Finalizar poderiam ser menu | P1 |
| 6 Card | Index já no padrão client (8.2.3) | — |
| 7 Unificar | Manter create no mesmo shell | P1 |
| 8 Fluxo longo | Form create pode ser wizard (dados → território → metas) | P2 |
| 9 Nav | OK no rail admin | — |
| 10 Nomenclatura | Consistente | — |
| 11 Campo morto | Revisar form completo em sprint de forms | P2 |
| 12 Ação | “Nova campanha” ok no toolbar | — |
| 13 Produtividade | Muitos cliques/taps por linha | P1 |
| 14 Drawer | Status / quick edit em drawer | P2 |
| 15 Modal | Confirmar pausar/finalizar | P2 |
| 16 Wizard | Create multi-step | P2 |
| 17 Quick actions | Menu “⋯” por linha | P1 |
| 18 Atalhos | `/` focar busca | P3 |
| 19 Mobile | Ações de linha críticas | P1 |
| 20 Cliques | Overflow menu −2 taps | P1 |

## Problemas

### P1 — Shell thrash
Create/edit usam `layouts.app`. Usuário perde o rail e a orientação
espacial.

### P1 — Ações densas por linha
Visitas / Editar / Ativar / Pausar / Finalizar competem; em notebook
estreito viram wall of buttons.

### P2 — Empty state legado
Ainda pode usar `empty-friendly` em vez de `x-client.empty-state`.

## Oportunidades

| Ideia | Tipo | Impacto | Estimativa |
|-------|------|---------|------------|
| Create/edit em `layouts.operational` | Consistência | Alto | 1 d |
| Menu overflow por linha | Menos cliques / mobile | Alto | 1 d |
| Confirmação modal para Finalizar | Menos erro | Médio | 0,5 d |
| Wizard create | Onboarding campanha | Médio | 2–3 d |
| `x-client.empty-state` | Consistência | Baixo | 0,25 d |
