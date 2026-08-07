# Performance de produtividade (cliques, tempo, consistência visual)

Não é performance de servidor — é **tempo humano** e atrito visual.

## Número médio de cliques (estimativas de auditoria)

| Papel | Tarefa | Cliques hoje | Alvo | Gap |
|-------|--------|--------------|------|-----|
| Vendedor | Registrar visita (mapa) | 3–5 | 3 | Baixo |
| Vendedor | Completar retorno | 4–7 | 3–4 | Médio |
| Vendedor | Ver “resultado” (acha o item) | 1–2 + hesitação label | 1 | Glossário |
| Gestor | Pausar campanha | 2–3 (achar botão) | 2 (menu) | Médio |
| Gestor | Aprovar 10 comissões | ~30–40 | ~5 (bulk) | Alto |
| Admin | Abrir Configurações | ∞ se não souber URL | 2 (Mais) | **P0** |
| Admin | Ver funil em Relatórios | 2 + frustração | 2 + ver gráfico | **P0** |
| Qualquer | Create campanha a partir do índice | 2 + reorientação shell | 2 | Alto |

## Tempo para tarefas (qualitativo)

| Tarefa | Tempo percebido | Motivo |
|--------|-----------------|--------|
| Campo no mapa | Rápido | Drawer + próxima casa |
| Admin setup | Lento | Settings órfão + Plano/Assinatura split |
| Análise semanal | Frustrante | Relatórios sem conteúdo |
| CRM move estágio | Médio-alto | Sem drag |
| Equipe permissões | Médio | Drawer denso mas sem full page |

## Consistência visual (cross-cutting)

| Dimensão | Estado | Sev |
|----------|--------|-----|
| Espaçamento | Tokens `--client-space-*` só onde `client-ui` | P1 |
| Tipografia | Mista (operational vs Tailwind map vs team) | P1 |
| Ícones | Lucide vs emoji vs texto | P0/P1 |
| Hierarquia | Dashboard ok; CRM/Mapa competem | P0 |
| Contraste | Bom no DS; risco no mapa slate/sky | P2 |
| Sombras/bordas | 8.2.3 removeu sombras no DS; ilhas ainda divergem | P2 |
| Modais/drawers | 4 famílias CSS | P1 |

## Três shells = custo cognitivo

Cada troca operacional → app ou → sales-app custa **~3–8 s** de
reorientação (rail some, sidebar aparece, brand strip muda). Em um dia
de admin isso se multiplica.

**Meta de produto:** no máximo **dois** shells mentais — Gestão
(operational) e Campo (mapa ou sales-app como skin), nunca três
layouts distintos para o mesmo tenant na mesma sessão de trabalho.

## Imports / CSS / HTML (fase “performance visual”)

Achados sem alterar comportamento nesta sprint:

| Item | Nota | Sev |
|------|------|-----|
| `MoreController::$links` morto p/ UI | Duplica modelo mental antigo | P2 |
| CSS `.team-*` paralelo a `client-ui` | Manutenção dupla | P1 |
| `maps/index` monolito Blade | Dificulta padronizar | P2 |
| Session alerts raw `.alert` | `x-client.alert` já existe | P2 |

## Acessibilidade (produtividade assistiva)

Já bom: skip-link, `#client-main`, focus-visible (8.2.3).  
Falta: mapa, team drawers, sales-app, kanban teclado (**P1/P2**).
