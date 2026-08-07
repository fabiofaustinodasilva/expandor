# Sprint 8.2.3 — Client UX Refinement

## Resumo

Refinamento de UX/UI sobre a base entregue em 8.2.2, **sem tocar** em
migrations, models, controllers, domain services, repositories, APIs,
`routes/web.php`, middleware, policies/gates/permissions, billing,
Mercado Pago, funcionalidade do App de campo (Sales App), autenticação
ou tenancy. Todo o trabalho ficou restrito a:
`public/css/client-ui.css`, `resources/views/**` (Blade),
`resources/views/components/client/**`, `tests/**` e `docs/**` — mais
duas adições pontuais de a11y (skip link / foco) em
`layouts/operational.blade.php` e `layouts/app.blade.php`.

## Auditoria (resumo dos achados)

- **Design system incompleto**: `client-ui.css` (8.2.2) só tinha estilos
  de layout dos componentes existentes — faltavam tokens (radius,
  spacing, foco), botão unificado com alvo de toque, alertas, skeleton,
  tooltip, chrome de modal/drawer, paginação e filtros colapsáveis.
- **Dashboard ainda com ruído**: filtros sempre abertos ocupando a
  primeira tela e um card de métrica "Financeiro do mês" que só
  repetia o nome do plano (zero valor informativo — já existe atalho
  para comissões/relatórios no cabeçalho).
- **Telas de listagem inconsistentes**: cada índice (`campaigns`,
  `customers`, `properties`, `crm.leads`, `commissions`, agenda de
  retornos) tinha seu próprio HTML solto para cabeçalho/ações/filtros,
  sem um padrão de "toolbar" reaproveitável.
- **Acessibilidade básica ausente**: nenhum skip link, sem
  `id`/`tabindex` no conteúdo principal para foco por teclado, e o
  `:focus-visible` só existia via `rc-ux-polish` (fallback genérico,
  não integrado ao design system do cliente).
- **Componentes de botão sem alvo de toque mínimo** (44px) e sem classe
  unificada para estados de foco/disabled consistentes.

## O que foi feito

### A) Design system (`public/css/client-ui.css`)

Adicionada uma nova seção "Sprint 8.2.3" (sem remover nada do 8.2.2),
usando somente as variáveis de tema já existentes
(`--primary`, `--border`, `--bg-elevated`, `--text`, `--muted`,
`--success`, `--warning`, `--highlight`):

- Tokens: `--client-radius(-sm/-lg)`, `--client-space-1..5`,
  `--client-focus-ring`.
- `.client-btn` — base unificada de botão (min-height 44px,
  `:focus-visible` próprio).
- Polimento de formulário escopado a `.client-ui` (`.form-control`,
  `select`, `input`, `textarea`, `.client-input`) com foco consistente.
- `.client-data-table` — cabeçalho `sticky`, zebra opcional
  (`--zebra`), células mais generosas, hover de linha.
- `.client-alert--success|error|warning|info`.
- `.client-skeleton` (+ variantes `text|title|block|circle`) com
  animação de shimmer.
- `.client-tooltip` acessível (hover + `:focus-within`).
- `.client-modal` / `.client-drawer` — chrome apenas visual (sem
  lógica), pronto para qualquer implementação futura.
- `.client-pagination` — wrapper estilizado para `$paginator->links()`.
- `.client-filters-collapsible` — `details`/`summary` nativo, sem JS.
- `.client-crud-toolbar` (+ `__search`, `__filters`, `__actions`).
- `.client-loading` — spinner inline com `aria-busy`.
- `.client-skip-link` e `.sr-only` (utilitário de acessibilidade).
- Radius consistente (`0.875rem`), sombras removidas de
  `.card`/`.client-section-card`/`.client-metric-card`/`.client-empty-state`.
- Mobile: espaçamento mais denso (`@media max-width: 640px`).
- `prefers-reduced-motion: reduce` — desativa animações/transições.
- `:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px }`
  reforçado globalmente.

### B) Novos componentes Blade (`resources/views/components/client/`)

1. `alert.blade.php` — `type="success|error|warning|info"`, ícone
   Lucide, `role="alert"` (error) ou `role="status"` (demais).
2. `skeleton.blade.php` — `variant="text|title|block|circle"`,
   `width`/`height` opcionais.
3. `tooltip.blade.php` — acessível via `tabindex`, `role="tooltip"`.
4. `crud-toolbar.blade.php` — slots `search`, `filters`, `actions`
   (todos opcionais).
5. `loading.blade.php` — spinner com `role="status"` e
   `aria-busy="true"`.
6. `pagination-bar.blade.php` — aceita `:paginator="$items"` (renderiza
   `$items->links()`) ou um slot padrão livre.

Componentes existentes aprimorados:

- `primary-button` / `secondary-button` / `danger-button` — agora
  também recebem a classe `client-btn` (alvo de toque 44px, foco
  consistente); já tinham `type="button"` como padrão quando não é
  link.
- `page-header` — novo prop opcional `$eyebrow` (rótulo pequeno acima
  do título).
- `empty-state` — já tinha `role="status"` (confirmado, sem alteração
  necessária).
- `page-breadcrumb` — já expõe `aria-label` na `<nav>` (mantido em
  PT-BR — "Trilha de navegação" — por consistência de idioma com o
  restante da aplicação).
- `data-table` — novo prop opcional `zebra` (aplica
  `client-data-table--zebra`).

### C) Dashboard mais enxuto (`resources/views/dashboard/index.blade.php`)

- Filtros agora dentro de `<details class="client-filters-collapsible">`
  (fechado por padrão, `<summary>Filtros</summary>`) — a primeira
  tela mostra direto as métricas e o ranking.
- Removido o card de métrica "Financeiro do mês" (apenas repetia o
  nome do plano); o acesso a comissões/relatórios continua disponível
  nas ações rápidas do cabeçalho ("Comissões" / "Ver relatórios").
- Nenhum emoji remanescente na tela.
- Espaçamento mantido consistente com os tokens do design system
  (`client-btn` nos botões do formulário de filtros).

### D) Padrão `crud-toolbar` aplicado às telas de listagem

Wrapping mínimo e não invasivo — nenhuma tabela, formulário ou rota
foi removida, apenas reorganizada visualmente:

- `campaigns/index.blade.php` — `page-header` + `crud-toolbar` (slot
  `actions` com "Nova campanha"); tabela ganhou `client-data-table`;
  paginação via `x-client.pagination-bar`.
- `customers/index.blade.php` — `page-header` (ação "Abrir mapa") +
  `crud-toolbar` (slot `search` com o formulário de busca existente);
  paginação via `x-client.pagination-bar`.
- `sales/properties/properties/index.blade.php` — `page-header` (ação
  "Novo ponto") + `crud-toolbar` (slot `filters` com o select de
  status); tabela ganhou `client-data-table`; paginação via
  `x-client.pagination-bar`.
- `crm/leads/index.blade.php` — `page-header` (ação "Novo lead") +
  `crud-toolbar` (slot `filters`); tabela ganhou `client-data-table`;
  paginação via `x-client.pagination-bar`.
- `visits/follow-ups/index.blade.php` (Agenda / retornos) —
  `page-header` (ação "Abrir mapa"); paginação via
  `x-client.pagination-bar`. Modal de conclusão de retorno e todo o
  JavaScript associado ficaram intocados.
- `commissions/index.blade.php` — `page-header` (ação "Produtos /
  Estoque" quando gestor) + `crud-toolbar` (slot `filters` com o
  formulário de período/vendedor/campanha/produto/status); tabela
  ganhou `client-data-table`; paginação via `x-client.pagination-bar`.

### E) `operations/more.blade.php` e `reports/index.blade.php`

- `operations/more.blade.php` — os cartões de seção agora são
  organizados em `grid grid-2` (antes, empilhados verticalmente),
  mantendo os mesmos dados (`ClientNav::sections()`), sem emojis.
- `reports/index.blade.php` — já usava `section-card` em `grid grid-3`
  de forma consistente com o design system; nenhuma alteração
  estrutural necessária (auditoria confirmou consistência visual e
  ausência de emojis).

### F) `operations/team.blade.php`

- Abas do hub (`client-hub-tabs` / `client-hub-tab`) já seguiam o
  padrão do design system — adicionado `aria-current="page"` na aba
  ativa para acessibilidade.
- O CSS local denso (`.team-*`) foi mantido intencionalmente: essas
  classes são consumidas apenas para estilo (os seletores usados pelo
  JavaScript dos drawers são `.js-open-edit`, `.js-open-perms`,
  `#drawer-*`, IDs de formulário — nenhum deles depende de
  `.team-btn-*`/`.team-card`). Uma reescrita completa para usar
  `.client-btn`/`.client-section-card` foi avaliada, mas envolveria
  tocar em ~15 seletores de estilo dentro de um fluxo com 3 drawers
  (criar/editar/permissões) e formulários dinâmicos — risco de
  regressão desproporcional ao ganho visual nesta sprint. Nenhum
  drawer ou funcionalidade foi removido ou alterado.

### G) Acessibilidade nos layouts

- `layouts/operational.blade.php` e `layouts/app.blade.php`: `<body>`
  ganhou a classe `client-ui` (habilita o polimento de formulário do
  design system) e, logo no início do `<body>`, o skip link
  `<a class="client-skip-link" href="#client-main">Ir para o
  conteúdo</a>`.
- O wrapper de conteúdo principal (`.op-main` em `operational`,
  `<main class="content">` em `app`) recebeu `id="client-main"` e
  `tabindex="-1"` para receber foco de teclado ao ativar o skip link.
- Nenhuma rota, controller ou lógica de navegação foi alterada.

### H) Testes

`tests/Feature/Release/Sprint823ClientUxRefinementTest.php` — 6 casos:

1. Filtros do dashboard colapsados por padrão + métrica "Financeiro do
   mês" removida + "Funil comercial" ausente.
2. Skip link e `id="client-main"` presentes no dashboard.
3. `campaigns.index` renderiza 200 com `client-crud-toolbar`.
4. `customers.index` renderiza 200 com `client-crud-toolbar`.
5. `layouts.app` e `layouts.operational` expõem `client-ui` / skip
   link.
6. Novos componentes (`alert`, `skeleton`, `loading`, `tooltip`,
   `crud-toolbar`, `pagination-bar`) renderizam sem erros via
   `Blade::render()`.

### I) Este documento

`docs/sprint-823-client-ux-refinement/README.md`.

## Contagem de componentes

- 6 componentes novos em `resources/views/components/client/`
  (`alert`, `skeleton`, `tooltip`, `crud-toolbar`, `loading`,
  `pagination-bar`).
- 4 componentes existentes aprimorados (`primary-button`,
  `secondary-button`, `danger-button`, `page-header`; `data-table`
  ganhou prop `zebra`).
- Total de componentes `client.*` na área do cliente: **21** (15 do
  8.2.2 + 6 novos).

## Telas alteradas

- Dashboard (`/dashboard`) — filtros colapsáveis, sem card financeiro.
- Campanhas (`/campaigns`), Clientes (`/clientes`), Pontos
  (`/properties`), Leads CRM (`/crm/leads`), Agenda
  (`/agenda`/follow-ups), Comissões (`/comissoes`) — padrão
  `crud-toolbar`.
- Mais opções (`/operacao/mais`) — grid de cartões.
- Equipe (`/operacao/equipe`) — `aria-current` na aba ativa.
- `layouts.app` / `layouts.operational` — skip link + `client-ui`.

## Antes / depois (resumo)

| Tela | Antes | Depois |
|---|---|---|
| Dashboard | Filtros sempre visíveis + card "Financeiro do mês" | Filtros em `<details>` fechado; card financeiro removido |
| Campanhas/Clientes/Pontos/Leads/Comissões | Header + ações soltos em `<div style="...">` | `x-client.page-header` + `x-client.crud-toolbar` |
| Botões `client.*` | Sem alvo de toque mínimo garantido | `.client-btn` (min-height 44px) em todos |
| Foco de teclado | Só fallback genérico do `rc-ux-polish` | `:focus-visible` dedicado do design system + skip link |
| Tabelas `client.*` | Sem cabeçalho fixo/zebra | `sticky thead`, zebra opcional, hover de linha |

## Itens futuros (fora do escopo desta sprint)

- Migrar o CSS denso e específico de `operations/team.blade.php`
  (`.team-*`) para os componentes `client.*` (avaliar em uma sprint
  dedicada, com testes de regressão de UI para os 3 drawers).
- Adotar `x-client.crud-toolbar` também em telas administrativas fora
  do fluxo comercial principal (ex.: `commissions/products/index`,
  territórios) se o padrão se mostrar útil.
- Adotar `x-client.alert` nos avisos de sessão (`session('success')`)
  dos layouts — não incluído aqui por estar fora do escopo estrito de
  a11y permitido para `layouts/*` nesta sprint.
- Revisar o uso de emojis em `App\Support\CommercialTerminology`
  (labels de status de propriedade/visita/comissão) em uma sprint que
  inclua esse `Support` class no escopo permitido de alteração.

## Nota sobre labels sem emoji

`SalesCommissionModuleTest::test_dashboard_commissions_button_links_by_role`
foi atualizado nesta sprint para esperar `Comissões` / `Minha comissão`
(sem emoji), alinhado ao dashboard desde 8.2.2/8.2.3.

## Testes

```
.\.tools\php\php.exe artisan test --filter=Sprint823
```
6 passed (29 assertions).

```
.\.tools\php\php.exe artisan test --filter=Sprint822
```
5 passed (20 assertions) — sem regressão.

```
.\.tools\php\php.exe artisan test --filter="Sprint823|Sprint822|test_dashboard_commissions_button_links_by_role"
```
12 passed (56 assertions).
