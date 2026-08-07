# Quick wins — só UX, alto impacto, baixa estimativa

Critérios: ≤ 1 dia, sem tocar DB/controllers/regras, preferindo Blade /
CSS / `ClientNav` Support / copy.

| ID | Win | Sev | Módulo | Est. | Impacto |
|----|-----|-----|--------|------|---------|
| QW-01 | Incluir Configurações no Mais (`ClientNav`) | P0 | Settings | 0,25 d | Descoberta |
| QW-02 | Copy honesta em Relatórios (renomear ou disclaimer) | P0 | Reports | 0,5 d | Confiança |
| QW-03 | Renomear “Nova oportunidade” → “Novo ponto” no mapa | P0 | Maps | 0,5 d | Glossário |
| QW-04 | Seller rail “Resultado” alinhado ao H1 da página | P1 | Dashboard | 0,25 d | Clareza |
| QW-05 | Remover emojis: settings, products, my-visits, customers/show | P1 | Vários | 0,5–1 d | Consistência |
| QW-06 | Filtros comissões em `<details>` fechado | P1 | Financeiro | 0,25 d | Densidade |
| QW-07 | Título página Comissões = “Financeiro” (ou vice-versa) | P1 | Financeiro | 0,25 d | Nav |
| QW-08 | Empty states → `x-client.empty-state` (campanhas/pontos/leads) | P2 | CRUDs | 0,5 d | Padrão |
| QW-09 | Label CRM interno “Regras de comissão” | P1 | CRM | 0,25 d | Dual comissões |
| QW-10 | Overflow menu (⋯) nas ações de campanha | P1 | Campaigns | 1 d | Mobile |
| QW-11 | Um `page-header` na Equipe (remover dual) | P1 | Team | 0,5 d | Hierarquia |
| QW-12 | Dashboard: segmented period + 1 CTA primária | P1 | Dashboard | 0,5–1 d | Ruído |
| QW-13 | Confirm modal Convert lead (UI) | P1 | CRM | 0,5–1 d | Erro |
| QW-14 | Banner “CRUD usuários moveu-se para Equipe” em `/users` | P1 | Team | 0,25 d | Duplicação |
| QW-15 | Sync/delete dead strings `MoreController::$links` | P2 | Nav | 0,25 d | Higiene* |

\*QW-15 toca controller — só se sprint de implementação autorizar
“cleanup sem comportamento”; senão deixar para sprint com escopo
controller.

## Pacote sugerido “Sprint 8.2.5 Quick Wins”

QW-01 … QW-09 + QW-11 + QW-12 ≈ **3–4 dias** de UI, risco baixo,
sensação imediata de produto mais premium e honesto.

## O que NÃO é quick win

- Rebuild de Relatórios com gráficos reais  
- Migrar Team CSS inteiro  
- Unificar Sales App + Mapa  
- Kanban com drag  
- Bulk approve comissões (pode precisar API)

Ver `roadmap.md`.
