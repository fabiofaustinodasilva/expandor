# UX — Diretrizes de simplificação

> Sprint 8.2.1 · Propostas visuais/estruturais. Sem implementar componentes novos de negócio.

## Objetivos

- Menos ruído, mais hierarquia.  
- Um vocabulário ([glossary.md](./glossary.md)).  
- Mobile-first no campo (Sales App + rail); gestão web limpa.

---

## Eliminar excesso visual

| Padrão atual | Diretriz |
|--------------|----------|
| Emoji em menus (`MoreController`) | Remover; ícones Lucide consistentes |
| Muitos cards no Dashboard | Máx. widgets oficiais ([dashboard.md](./dashboard.md)) |
| Duplo header (título + meta + 5 botões) | Primário: título + 1–2 CTAs |
| Tabelas densas sem filtros salvos | Filtros colapsáveis; paginação sempre |
| Sidebar + rail + bottom nav competindo | Um primário + Sales App campo |
| Badges/emoji em status misturados | Preferir `CommercialTerminology` sem novos emojis em nav |

---

## Botões

- Primário: uma ação principal por view.  
- Destrutivos: confirmação explícita (já comum).  
- Ghost/secundário: no máximo 2 no header.  
- Evitar botões só com emoji.

---

## Cards

- Card = unidade de decisão ou métrica — não decoração.  
- Não aninhar card dentro de card.  
- Ativação: no máximo 1 banner, não 3 cards.

---

## Tabelas

- Colunas essenciais first; resto em “detalhe”.  
- Sticky header em listas longas (CSS).  
- Empty state com CTA único (“Criar campanha”, “Abrir mapa”).

---

## Atalhos

| Permitidos na home | Evitar |
|--------------------|--------|
| Abrir mapa | 6+ atalhos de módulos |
| Minha comissão (seller) | Links para Config no Dashboard |
| Retornos pendentes (count → agenda) | Atalhos duplicados do rail |

---

## Breadcrumbs

- Introduzir em CRUDs (`layouts.app`): Operação / Pontos / Editar.  
- Mapa fullscreen: sem breadcrumb (chrome mínimo).  
- Sales App: sem breadcrumb (bottom nav).

---

## Modais

- Preferir página para formulários longos (visita complexa).  
- Modal só para confirmações e forms curtos.  
- Um modal por vez; foco trap acessível.

---

## Scroll & responsividade

| Breakpoint | Comportamento alvo |
|------------|--------------------|
| ≤900px | Rail → bottom bar (já existe); revisar touch ≥44px |
| Sales App | Manter; glossário alinhado |
| `layouts.app` forms | Evitar scroll horizontal; stacks |
| Dashboard | Widgets em grid 2→1 coluna no mobile |

### Problemas conhecidos (8.2.0) a corrigir na 8.2.2

- Telas clássicas com sidebar + conteúdo apertado no mobile.  
- Scroll excessivo no Dashboard com ativação + filtros + funil.  
- Botões pequenos no rail em alguns densidades.

---

## Lista de melhorias UX (priorizada)

| # | Melhoria | Prioridade | Complexidade |
|---|----------|------------|--------------|
| 1 | Aplicar glossário em todos os menus | Alta | Baixa |
| 2 | Shell de navegação único | Alta | Alta |
| 3 | Dashboard enxuto | Alta | Média |
| 4 | Remover emoji do Mais | Baixa | Baixa |
| 5 | Breadcrumbs em CRUD | Média | Baixa |
| 6 | Empty states padronizados | Média | Baixa |
| 7 | Touch targets mobile rail/app | Média | Baixa |
| 8 | Unificar Plano + Assinatura numa Config | Média | Média |
| 9 | Banner único de ativação | Média | Média |
| 10 | Densidade tipográfica consistente | Baixa | Média |
