# Sprint 8.2.6 — Mobile Responsiveness

## Objetivo

Tornar a Área do Cliente **mobile-first e totalmente responsiva**, sem
alterar regras de negócio, banco, controllers, services, APIs, billing
ou permissões.

## Auditoria (Fase 1) — resumo

| Achado | Severidade | Mitigação nesta sprint |
|--------|------------|-------------------------|
| Bottom rail overcrowded (≤900px) | P0 | Substituído por **hamburger + drawer** |
| `.table { min-width: 480px }` força scroll | P0 | Override + card mode ≤640px |
| App shell hamburger sem backdrop | P1 | Drawer fixo + backdrop + fecha ao navegar |
| Tabelas sem modo card | P1 | `client-data-table--responsive` + `data-label` JS |
| Map drawers laterais no mobile (gestor) | P1 | Bottom sheet ≤900px para marker/metrics |
| Grids 4→1 sem passo tablet | P2 | 2 colunas em 641–900px |
| Modais centralizados em phone | P2 | Bottom sheet ≤640px |
| Search `min-width: 220px` | P2 | `min-width: 0` no mobile |

Detalhe por módulo: ver [CHECKLIST.md](./CHECKLIST.md).

## O que foi feito

### Navegação
- **Desktop (≥901px):** rail fixa à esquerda (inalterada em espírito).
- **≤900px:** barra superior + botão Menu → drawer lateral; backdrop;
  Escape; fecha ao clicar link ou fora.
- **App shell:** mesmo padrão de drawer + backdrop via `client-mobile.js`.

### Design system (`client-ui.css`)
- Tokens `--client-bp-*`, `--client-touch` (44px), `--client-drawer-w`.
- Tabelas → cards no phone; formulários 100%; modais/drawers bottom-sheet.
- Grids KPI: 4→2 (tablet) →1 (phone).
- Mapa: painéis em bottom sheet.

### JS
- `public/js/client-mobile.js` — nav, labels de tabela, wrap de scroll.

## Fora de escopo / próximo

- Unificar Sales App com o shell operational (já é mobile-first).
- Migrar Tailwind island do mapa para tokens 100%.
- Testes visuais automatizados por viewport (Playwright) — checklist manual.

## Testes

```
.\.tools\php\php.exe artisan test --filter=Sprint826
```

5 passed (+ regressão Sprint823/825).

## Artefatos

- [CHANGELOG.md](./CHANGELOG.md)
- [CHECKLIST.md](./CHECKLIST.md)
- [screenshots/](./screenshots/)
- Canvas: `sprint-826-mobile-responsive.canvas.tsx`
