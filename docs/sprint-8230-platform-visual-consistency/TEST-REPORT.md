# TEST-REPORT — 8.2.30

## Suite nova

`tests/Feature/Release/Sprint8230PlatformVisualConsistencyTest.php`

**OK (18 tests, 120 assertions)** — estrutural, não pixel.

| # | Cobertura |
|---|-----------|
| 1 | design tokens (`--color-*`, radius, type) |
| 2 | button variants (primary/ghost/danger/icon/disabled/focus) |
| 3 | form controls (radius + focus-visible) |
| 4 | badges (success/warning/danger/info/primary) |
| 5 | cards (section + metric) |
| 6 | tables (responsive + `.table-num`) |
| 7 | empty states (produtos, visitas) |
| 8 | toast/alert + commission-reward intacto |
| 9 | page headers nas telas-chave |
| 10 | responsive + skip-link + touch 44px |
| 11 | glossário (Ponto; sem 📍 Residência / 🏠) |
| 12–15 | Seller / Manager / Admin / Super Admin |
| 16 | auth + 403/404/419/500 |
| 17 | a11y primitives |
| 18 | `MapMarkerColor` intacto |
| 19 | `AppTime` contract intacto |
| 20 | `ProductCommissionType` intacto |
| 21 | zero migrations `*8230*` |
| 22 | Equipe: login / atividade / visita |

## Regressão

Filtro: Sprint8229, 8228, 8226, 8225, 8224, 8223 reward + V2, 8222 GoogleMapsProvider, 8219 map surface, MapsModule, Sprint822 Client UI, AgendaFollowUps, SalesApp, SalesCommission, ProductCatalog, Sprint8215 Products, Campaigns, TeamPresence, TeamHub, + 8230.

**OK (208 tests, 1176 assertions)**

Ajuste de copy para não mascarar 8.2.15: CTA `+ Novo produto` e trilha `Área da Empresa → Produtos` preservados no header de Produtos.

## Não mascarado

Falha inicial 8215 (`+ Novo produto` / breadcrumb) foi **corrigida no chrome**, não no teste.
