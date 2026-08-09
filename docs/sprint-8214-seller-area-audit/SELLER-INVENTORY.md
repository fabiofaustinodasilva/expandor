# SELLER-INVENTORY

Fonte: `ClientNav`, `NavVisibility`, `RolePermissionSeeder` (Role::SELLER), controllers/policies.

| Função | Rota | Tela | Permissão | Onde aparece | Uso real | Prioridade |
|--------|------|------|-----------|--------------|----------|------------|
| Mapa operacional | `map.index` | maps/index | `maps.view` | Rail + login home | Centro do dia | **P0** |
| Minha localização | JS `#btn-recenter-location` | mapa | — | Toolbar mapa | GPS | **P0** |
| Registrar ponto (toque) | `map.first-approach` | modal ponto | `properties.*` + `visits.manage` | Mapa | 1ª abordagem | **P0** |
| Visita em ponto existente | `map.visits.store` | modal visita | `visits.manage` | Drawer mapa | Revisita | **P0** |
| Apresentar produtos | `sales-app.products.present` | present.blade | `sales_app.access` (+ flag mobile) | Rail + CTA mapa | Venda presencial | **P0** |
| Detalhes produto | JS bottom-sheet | present | — | Deck | Consulta/treino | **P0** |
| Contratar | `map.index?contract_product=` | FirstApproach | mesmas do mapa | Deck | Fechar venda | **P0** |
| Agenda / retornos | `follow-ups.index` | visits/follow-ups | `visits.view` | Rail “Agenda” | Retornos | **P0** |
| Completar retorno | `follow-ups.complete` | agenda modal | FollowUp own | Agenda | Fechar retorno | **P0** |
| Comissão | `commissions.index` | commissions | `commissions.view_self` | Rail | Resultado financeiro | **P1** |
| Resultado (dashboard) | `dashboard` | dashboard | `dashboard.view` | Rail | Métricas dia | **P1** |
| Clientes | `customers.index/show` | customers | `customers.view` | Rail | Consulta | **P1** |
| Lista produtos Sales App | `sales-app.products.index` | sales-app | sales_app | Bottom Sales App | Entrada alt. | **P1** |
| Retornos Sales App | `sales-app.follow-ups.index` | sales-app | sales_app | Bottom “Retornos” | Duplicata agenda | **P2** |
| Campanhas Sales App | `sales-app.campaigns.*` | sales-app | sales_app | Bottom | Território | **P1** |
| Academia Sales App | `sales-app.training.*` | sales-app | sales_app + training | Bottom | Treino | **P2** |
| Academia web | `training.categories.index` | training | `training.view` | Mais | Treino | **P2** |
| App de campo home | `sales-app.dashboard` | sales-app | sales_app | Mais | Paralelo ao mapa | **P2** |
| CRM / Leads | `crm.*` | CRM | `crm.view/manage` | Mais | Administrativo no campo | **P3** |
| Regras de comissão | `crm.commissions.index` | CRM | `crm.view` | Mais | Admin | **P3** |
| Pontos (lista) | `properties.index` | properties | `properties.view` | Mais | Paralelo mapa | **P2** |
| Cidades / Setores | `cities/sectors.index` | territory | view | Mais | Raro no dia | **P3** |
| Campanhas (web) | `campaigns.index` | campaigns | `campaigns.view` | Mais | Raro | **P2** |
| WhatsApp / AI | communication / ai | — | flag+plan | Mais | Condicional | **P2** |
| Perfil / logout | `profile.edit` / logout | profile | auth | Rail fixo | Conta | **P1** |
| Mais | `operations.more` | more | auth | Rail | Overflow | **P1** |
| CRUD Produtos | `commissions.products.*` | — | `commissions.manage` | — | **Bloqueado** seller | — |
| Aprovar/pagar comissão | commissions.approve/pay | — | manage | — | Bloqueado | — |

## Contagem aproximada

- Funções P0 de campo: ~10  
- Entradas de navegação rail seller: 6 + Perfil + Mais + Sair  
- Bottom Sales App: 5  
- Itens Mais potencialmente administrativos: CRM, regras, território
