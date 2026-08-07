# Equipe — auditoria UX

**Views:** `operations/team.blade.php` (+ drawers internos)  
**Rota:** `operations.team` → `/operacao/equipe`  
**Shell:** operational  
**Paralelo legado:** `/users` (CRUD clássico, fora do ClientNav)

## Clareza imediata

Hub com abas (Usuários / Funções / Permissões / Metas / Comissões) é
o caminho certo. Visualmente ainda é **CSS `.team-*` denso**, dual
header e drawers custom — não parece o mesmo produto das listagens
8.2.3 (**P1**).

## Checklist das 20 perguntas

| # | Achado | Sev |
|---|--------|-----|
| 1 Clareza | Alta (hub) | — |
| 2 Excesso | Dual header (hub tabs + header local) | P1 |
| 3 Escondido | Permissões no drawer — ok, mas denso | P2 |
| 4 Duplicação | `/users` ainda existe | P1 |
| 5 Botão | Múltiplos `.team-btn-*` | P2 |
| 6 Card | Cards de membro ok; estilo fora do DS | P2 |
| 7 Unificar | Aba Usuários = único CRUD; aposentar `/users` na UX | P1 |
| 8 Fluxo | Criar usuário em drawer (bom) vs página | — positivo |
| 9 Nav | No rail admin — excelente | — |
| 10 Nomenclatura | Aba “Usuários” vs glossário “Equipe” | P2 |
| 11 Campo | Forms de permissão longos | P2 |
| 12 Ação | Editar / perms nos cards — ok | — |
| 13 Produtividade | Drawers evitam full page (bom) | — |
| 14 Drawer | Já usa; migrar chrome → `client-drawer` | P1 |
| 15 Modal | Confirmar desativar usuário | P2 |
| 16 Wizard | Convidar: dados → papel → permissões | P2 |
| 17 Quick actions | Convidar no header único | P2 |
| 18 Atalhos | — | P3 |
| 19 Mobile | Drawers largos; cards empilham | P2 |
| 20 Cliques | Fluxo drawer já reduz cliques | — |

## Problemas

### P1 — Fora do design system
Chamado explicitamente em 8.2.3 como futuro. Risco alto se migrar sem
testes dos 3 drawers (criar / editar / permissões).

### P1 — Dualidade `/users`
Bookmark/legado compete com Equipe. Na UX: deep-link de `/users` →
Equipe ou banner “movido para Equipe”.

### P2 — Aba “Usuários”
Preferir “Membros” sob o hub Equipe.

## Oportunidades

| Ideia | Impacto | Estimativa |
|-------|---------|------------|
| Um `page-header` + hub-tabs | Médio | 0,5 d |
| Migrar drawers para `client-drawer` + `client-btn` | Alto | 3–5 d (com testes) |
| Redirect UX `/users` → equipe | Médio | 0,5 d |
| Wizard convite | Médio | 2 d |
| `aria-current` já presente (8.2.3) — manter | — | — |
