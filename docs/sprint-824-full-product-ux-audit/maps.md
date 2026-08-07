# Mapa — auditoria UX

**Views:** `resources/views/maps/index.blade.php` (~900+ linhas)  
**Rota:** `map.index` → `/map`  
**Shell:** `layouts.operational` (conteúdo Tailwind isolado)

## Clareza imediata

O mapa é intuitivo como **ferramenta de campo**: pins, busca, próxima
casa, drawer do ponto. Porém a linguagem visual (emoji 👣📞💬) e o CTA
**“Nova oportunidade”** (cria **ponto**, não oportunidade de CRM)
quebram o glossário Expandor (**P0**).

## Checklist das 20 perguntas

| # | Achado | Sev |
|---|--------|-----|
| 1 Clareza | Função clara; nomenclatura “oportunidade” confunde | P0 |
| 2 Excesso | Camadas + métricas + filtros + sheets | P1 |
| 3 Escondido | Controles só-ícone / emoji sem label acessível | P1 |
| 4 Duplicação | Mesmas ações no Sales App e Agenda | P1 |
| 5 Botão desnecessário | Vários CTAs “Nova oportunidade” repetidos | P2 |
| 6 Card desnecessário | Painéis de métrica densos para seller | P2 |
| 7 Unificar | Unificar outcome de visita com agenda/sales-app | P1 |
| 8 Fluxo longo | Registro de visita OK; formulário de ponto pode alongar | P2 |
| 9 Nav | Brand mark → mapa (bom para campo) | — |
| 10 Nomenclatura | Oportunidade ≠ ponto ≠ lead CRM | P0 |
| 11 Campo morto | Auditar campos raros do modal de ponto | P2 |
| 12 Ação importante | “Próxima casa” forte; editar ponto menos óbvio | P2 |
| 13 Produtividade | Fluxo de campo é o melhor do produto | — (positivo) |
| 14 Drawer | Já usa drawer de marker — alinhar a `client-drawer` | P2 |
| 15 Modal | Bottom sheets — alinhar a `client-modal` | P2 |
| 16 Wizard | Criar ponto multi-etapa (endereço → contato → status) | P2 |
| 17 Quick actions | FAB “próxima casa” já é quick action | — |
| 18 Atalhos | Teclado no mapa limitado | P3 |
| 19 Mobile | Sheets + bottom rail colidem; landscape frágil | P1 |
| 20 Cliques | Melhor caminho do vendedor; polish de labels | P1 |

## Problemas

### P0 — Glossário “Nova oportunidade”
Três ocorrências no template criam **ponto** (`properties`), não
oportunidade CRM. Vendedor e gestor misturam conceitos com `/crm`.

### P0 — Ilha visual fora do design system
Tailwind slate + emoji vs Lucide/`client-ui` no resto. Sensação de
outro produto.

### P1 — Densidade de overlays no mobile
Filtros, métricas, drawer e bottom rail competem. Seller precisa de
modo “campo limpo” (já parcialmente existe; reforçar).

### P1 — Acessibilidade de ações
Botões com emoji como único sinal; risco de contraste e leitores de
tela.

## Oportunidades

| Ideia | Tipo | Impacto | Estimativa |
|-------|------|---------|------------|
| Renomear CTAs → “Novo ponto” | Glossário | Alto | 0,5 d |
| Trocar emoji por Lucide | Consistência | Alto | 1–2 d |
| Modo campo: 1 sheet de camadas | Mobile | Alto | 2 d |
| Chrome `client-drawer` / `client-modal` | Consistência | Médio | 2–3 d |
| Wizard criar ponto | Menos erro | Médio | 3 d |
| Componente compartilhado de outcome de visita | Unificação | Alto | 3–5 d |

## Fluxo vendedor (mapa)

1. Abre Mapa (rail)  
2. “Próxima casa” ou pin  
3. Registrar visita (sheet)  
4. Opcional: retorno → Agenda  

**Cliques médios estimados:** 3–5 até registrar visita (bom).  
**Risco:** confusão semântica no CTA de criação (+1–2 cliques de hesitação).
