# CRM — auditoria UX

**Views:** `crm/dashboard.blade.php`, `crm/leads/*`, `crm/opportunities/*`,
`crm/goals/*`, `crm/commissions/*`  
**Rotas:** `/crm`, `/crm/leads`, kanban/list, metas, regras de comissão  
**Shell:** predominantemente `layouts.app`

## Clareza imediata

O hub CRM funciona como **segundo dashboard** comercial, sem hierarquia
clara frente ao Dashboard principal (**P0** de produto/navegação).
Adoção de `x-client.*` é quase nula (**P1**).

## Checklist das 20 perguntas

| # | Achado | Sev |
|---|--------|-----|
| 1 Clareza | Média — compete com Dashboard e com “Clientes” da carteira | P0 |
| 2 Excesso | Cards + tabelas + kanban actions | P1 |
| 3 Escondido | Leads só via Mais / CRM | P2 |
| 4 Duplicação | “Clientes e oportunidades” vs rail Clientes | P1 |
| 5 Botão | Win/Lose/Move por card no kanban | P1 |
| 6 Card | Hub com cards genéricos | P2 |
| 7 Unificar | Hub com tabs: Leads \| Pipeline \| Metas \| Regras | P1 |
| 8 Fluxo | Converter lead → POST sem wizard/confirm | P1 |
| 9 Nav | Fora do rail; label longo no Mais | P1 |
| 10 Nomenclatura | “Comissões” no CRM = regras, não `/comissoes` | P1 |
| 11 Campo | Forms de oportunidade densos | P2 |
| 12 Ação | Converter lead pouco celebrado / sem undo UI | P1 |
| 13 Produtividade | Kanban sem drag = muitos cliques | P1 |
| 14 Drawer | Detalhe da oportunidade em drawer | P2 |
| 15 Modal | Confirmar Win/Lose/Convert | P1 |
| 16 Wizard | Convert lead → cliente/ponto | P1 |
| 17 Quick actions | Filtros de estágio no topo | P2 |
| 18 Atalhos | Ausentes | P3 |
| 19 Mobile | Kanban horizontal difícil | P1 |
| 20 Cliques | Move estágio = 2–3 cliques; drag seria 1 | P1 |

## Problemas principais

### P0 — Segundo painel
`crm.dashboard` não deixa claro que é módulo opcional/avançado vs
Dashboard operacional diário.

### P1 — Dual “Comissões”
`crm.commissions` (regras) vs `commissions.index` (campo). Labels no
Mais diferenciam (“Regras de comissão”), mas páginas internas ainda
podem dizer só “Comissões”.

### P1 — Zero design system
Tabelas/cards crus; inconsistente com campanhas/clientes pós-8.2.3.

### P1 — Convert lead sem confirmação
Risco de clique acidental e percepção de “sumiu o lead”.

## Oportunidades

| Ideia | Impacto | Estimativa |
|-------|---------|------------|
| CRM só via Mais + hub tabs unificadas | Alto | 2–3 d |
| Adoptar page-header / crud-toolbar / empty-state | Alto | 2 d |
| Modal confirm Convert / Win / Lose | Alto | 1 d |
| Renomear UI interna “Regras de comissão” | Médio | 0,5 d |
| Kanban leve com drag (depois) | Alto | 5+ d |
| Drawer de oportunidade | Médio | 3 d |

## Relação com carteira

| Conceito | Onde | Mensagem ao usuário |
|----------|------|---------------------|
| Cliente (carteira) | `/clientes` | Pessoa/empresa compradora |
| Ponto | `/properties` | Endereço de abordagem |
| Lead | `/crm/leads` | Interesse ainda não convertido |
| Oportunidade | CRM pipeline | Negócio em estágio |

A auditoria recomenda **não fundir** as entidades — só **rotular** e
educar na UI (tooltips / empty copy).
