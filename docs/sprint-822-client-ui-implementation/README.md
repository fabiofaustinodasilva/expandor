# Sprint 8.2.2 — Client Area UI Implementation

## Resumo

Implementação da UI da área do cliente sobre a base definida nos audits/
refactors anteriores (8.2.0 e 8.2.1), **sem tocar** em migrations, models,
controllers, domain services, repositories, policies, APIs, billing,
Mercado Pago, autenticação, middleware de tenancy ou `RolePermissionSeeder`.

Toda a lógica de visibilidade de navegação passa a ser centralizada em
`App\Support\ClientArea\NavVisibility` (regra única: permissão **AND**
flag opcional **AND** feature de plano opcional), consumida por
`App\Support\ClientArea\ClientNav` para montar o rail primário e as seções
de navegação secundária (usadas no menu lateral, na página "Mais" e nos
hubs de módulo).

## O que foi feito

1. **`App\Support\ClientArea\NavVisibility`** — `can(User $user, string $module): bool`
   para os 22 módulos do glossário (dashboard, map, campaigns, points,
   visits, sales, customers, team, crm, commissions, commission_rules,
   stock, reports, branding, integrations, whatsapp, ai, training, billing,
   sales_app, audit, privacy, territory). Tenants legados (sem plano, ou
   plano com `featureMap()` totalmente vazio/falso) nunca são bloqueados
   pela checagem de feature de plano — só passamos a exigir a feature do
   catálogo quando o plano já tem ao menos uma feature configurada como
   verdadeira.

2. **`App\Support\ClientArea\ClientNav`** — `railItems(User $user)` (rail
   primário, diferente para admin/gestor e vendedor de campo) e
   `sections(User $user)` (seções secundárias: Início, Comercial, Empresa,
   Sistema), ambos já filtrados por `NavVisibility`.

3. **15 componentes Blade** em `resources/views/components/client/`:
   `page-header`, `section-card`, `metric-card`, `empty-state`,
   `data-table`, `search-bar`, `filter-bar`, `quick-actions`,
   `primary-button`, `secondary-button`, `danger-button`, `success-badge`,
   `status-badge`, `entity-avatar`, `page-breadcrumb`. Usam as variáveis de
   tema já existentes (`--primary`, `--border`, `--bg-elevated`, etc.).

4. **Navegação**
   - `layouts/partials/app-nav.blade.php` reescrito para montar as seções a
     partir de `ClientNav::sections()` (glossário: "Pontos" em vez de
     "Clientes / Pontos", sem link direto para "Usuários" — apenas "Equipe").
   - `layouts/partials/client-rail.blade.php` (novo) — rail primário do
     `layouts/operational.blade.php`, que agora só faz `@include` dele.
   - `operations/more.blade.php` reescrito para construir os cartões a
     partir de `ClientNav::sections()` (o array `$links` do
     `MoreController` deixou de ser usado pela view, mas o controller não
     foi alterado).

5. **Rota + página de Relatórios** — `Route::view('/relatorios',
   'reports.index')->name('reports.index')` dentro do grupo
   `auth + tenancy.*` existente. A view é um hub estático (sem controller)
   com cartões para Dashboard (30d), Comissões, CRM e Equipe, cada um
   condicionado a `NavVisibility::can()`.

6. **Dashboard slim** — `dashboard/index.blade.php` reescrito para mostrar
   apenas: cabeçalho com `x-client.page-header`, toggles de período
   (Hoje/7d/30d), filtros existentes, métricas essenciais em
   `x-client.metric-card` (visitas, vendas/instalações, conversão, retornos
   pendentes, interessados/pendentes, vendedores ativos) e um ranking de
   vendedores compacto (top 5, sem gráfico). Funil comercial, lista de
   alertas, gráficos (Chart.js) e tabela de desempenho por região foram
   removidos da tela — os dados continuam calculados pelo
   `DashboardMetricsService` (não alterado) e ficam acessíveis via
   `reports.index`. Um link "Ver relatórios" foi adicionado ao cabeçalho.

7. **Hub da Equipe** — `operations/team.blade.php` recebeu abas/cartões no
   topo (Usuários, Funções, Permissões, Metas, Comissões) usando os
   componentes `client.*`, sem remover nenhuma funcionalidade existente
   (drawers de criar/editar/permissões continuam intactos).

8. **Glossário** — título e cabeçalho de `sales/properties/properties/index`
   trocados de "Clientes / Pontos" para "Pontos" (`CommercialTerminology::pointNoun()`
   / `points()` adicionados como helpers). "Clientes" (customers) permanece
   inalterado conforme o glossário.

9. **App de campo** — `layouts/sales-app.blade.php` recebeu a classe de
   body `sales-app-shell` e uma faixa "App de campo" no topo, puramente
   visual (bottom nav e labels mantidos).

10. **Breadcrumbs** — componente `x-client.page-breadcrumb` adicionado a 8
    telas: `properties/index`, `campaigns/index`, `customers/index`,
    `crm/leads/index`, `crm/leads/edit`, `cities/index`, `cities/edit`,
    `sectors/index`. Padrão: `Dashboard > Operação > <Módulo>`.

11. **CSS** — `public/css/client-ui.css` (novo) com os estilos dos
    componentes `client.*`, abas de hub e faixa do app de campo. Linkado em
    `layouts/app.blade.php`, `layouts/operational.blade.php` e
    `layouts/sales-app.blade.php`.

12. **Testes** — `tests/Feature/Release/Sprint822ClientUiImplementationTest.php`
    com 5 casos (visibilidade de nav sem permissão, dashboard slim sem funil,
    label "Pontos" na navegação, rota de relatórios e seções do hub da
    equipe). Além disso, 4 testes pré-existentes em
    `AnalyticsDashboardTest` foram ajustados para refletir a remoção
    intencional do funil/alertas/tabela de setores da tela — os dados
    continuam validados via `assertViewHas`, apenas as asserções de HTML
    que dependiam das seções removidas foram atualizadas.

## Contagem de componentes

- 15 componentes Blade em `resources/views/components/client/`.
- 2 classes de suporte (`NavVisibility`, `ClientNav`).
- 1 partial de navegação nova (`client-rail`), 1 reescrita (`app-nav`).
- 1 rota nova (`reports.index`) + 1 view nova (`reports/index`).
- 8 telas com breadcrumb adicionado.

## Telas afetadas

- Dashboard (`/dashboard`)
- Relatórios (`/relatorios`) — nova
- Mais opções (`/operacao/mais`)
- Equipe (`/operacao/equipe`)
- Navegação lateral (`layouts.app`) e rail (`layouts.operational`)
- App de campo (`layouts.sales-app`)
- Pontos, Campanhas, Clientes, Leads CRM, Cidades, Setores (breadcrumbs)

## Testes

```
.\.tools\php\php.exe artisan test --filter=Sprint822
```

5 passed (20 assertions).

Regressão verificada nas suítes relacionadas (`TeamHub`, `Analytics`,
`Operations`, `SalesApp`, `CRM`, `Sales`, `Territory`, `Customers`,
`Campaigns`): 63 passed (293 assertions) após os ajustes descritos no
item 12. Dois ajustes adicionais foram feitos para a suíte completa:

- `ManagerNavCommissionsIntegrationTest` — a asserção de "Produtos" no
  rail do gestor foi movida para a página `operations.more` (o link deixou
  de existir diretamente no hub da Equipe).
- `OnboardingModuleTest::test_progress_bar_api_mobile_and_platform_integrations`
  — a asserção `assertSee('Setup')` no dashboard foi trocada por
  `assertSee('Dashboard')`, já que o card de setup agora é colapsado
  intencionalmente quando `saasWorkspaceReady` é verdadeiro (item 5 do
  pedido original).

Suíte completa (`php artisan test`): **442 passed (2583 assertions)**,
sem falhas.
