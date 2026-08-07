# Changelog — Sprint 8.2.5 Quick Wins

## Added
- Módulo `settings` em `NavVisibility` + item **Configurações** na seção Empresa do Mais.
- Controles `.client-period-seg` e menu `.client-overflow` no design system (`client-ui.css`).
- Banner informativo em `/users` apontando para Equipe.
- Confirm JS nativo ao converter lead no CRM.
- Suite `Sprint825ClientUxQuickWinsTest` (7 casos).

## Changed
- Relatórios → **Atalhos de análise** (copy honesta; label no Mais atualizado).
- Mapa: CTAs **Novo ponto**; JS `operational-map.js` alinhado; potencial no `map-provider.js`.
- Dashboard: título **Resultado** para vendedor; período segmentado; CTA primária Abrir mapa; demais em Mais.
- Comissões: título **Financeiro** / **Comissão**; filtros em `<details>` fechado.
- CRM commissions: título **Regras de comissão**.
- Equipe: um `page-header`; aba **Membros**.
- Campanhas: ações densas → menu ⋯; empty-state `x-client`.
- Settings / products / my-visits / customers show: emojis removidos dos labels.

## Removed (UI only)
- Promessa falsa de funil/gráficos na página de Relatórios.
- Emojis 📦💰👣📞 etc. nas superfícies listadas acima.

## Compatibility
- Rotas, controllers, models, billing, permissões e regras de negócio intactos.
- Posts de ativar/pausar/finalizar campanha e converter lead inalterados (só UI).

## Tests updated
- Sprint822, MapsModuleTest, SalesCommissionModuleTest, ManagerNavCommissionsIntegrationTest, AnalyticsDashboardTest (asserções de copy).
