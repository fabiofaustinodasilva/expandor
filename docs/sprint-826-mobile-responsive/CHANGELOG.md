# Changelog — Sprint 8.2.6 Mobile Responsive

## Added
- `public/js/client-mobile.js` — drawer nav, table `data-label`, table-wrap.
- Tokens mobile e regras Sprint 8.2.6 em `public/css/client-ui.css`.
- Barra `.op-mobile-bar` + backdrop `.op-nav-backdrop` no layout operational.
- `Sprint826MobileResponsiveTest` (5 casos).
- Docs `docs/sprint-826-mobile-responsive/`.

## Changed
- Mobile nav operational: bottom bar overcrowded → **hamburger drawer**.
- App sidebar mobile: drawer fixo com backdrop (fecha fora / após navegar).
- Grids `.grid-3/4`: 2 colunas em tablet, 1 em phone.
- Tabelas `.client-data-table`: modo card ≤640px.
- Modais/drawers client/ux/team: bottom sheet ≤640px.
- Mapa `#marker-drawer` / `#metrics-panel`: bottom sheet ≤900px.
- `rc-ux-polish`: wrap de tabelas também em `.op-page`; toggle shell
  delegado ao `client-mobile.js` quando o script está presente.

## Removed (comportamento UI)
- Bottom tab bar sticky do operational ≤900px (substituída pelo drawer).

## Compatibility
- Nenhuma mudança de controller, model, migration, API, billing ou policy.
- Rotas e permissões intactas; apenas apresentação e navegação visual.
