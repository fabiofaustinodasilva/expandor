# Sprint 8.2.5 — Quick Wins da Auditoria 8.2.4

## Escopo

Implementação **somente** dos Quick Wins **P0 e P1** listados em
`docs/sprint-824-full-product-ux-audit/quickwins.md`.

Alterado: Blade, CSS, JS de mapa, `ClientNav` / `NavVisibility` (Support),
tests e docs. **Não** alterado: banco, migrations, models, controllers,
services, repositories, APIs, billing, tenancy, auth, policies.

## Quick Wins entregues

| ID | Item | Status |
|----|------|--------|
| QW-01 | Configurações no Mais (`ClientNav` + módulo `settings`) | Feito |
| QW-02 | Relatórios → copy honesta “Atalhos de análise” | Feito |
| QW-03 | Mapa “Novo ponto” (+ JS modal title) | Feito |
| QW-04 | Seller H1 “Resultado” alinhado ao rail | Feito |
| QW-05 | Strip emoji (settings, products, my-visits, customers/show) | Feito |
| QW-06 | Filtros Financeiro colapsáveis | Feito |
| QW-07 | Título página = Financeiro / Comissão | Feito |
| QW-09 | CRM “Regras de comissão” | Feito |
| QW-10 | Overflow ⋯ em campanhas | Feito |
| QW-11 | Equipe: um `page-header` + aba Membros | Feito |
| QW-12 | Dashboard: period seg + 1 CTA + Mais | Feito |
| QW-13 | Confirm ao converter lead | Feito |
| QW-14 | Banner `/users` → Equipe | Feito |
| QW-08 / QW-15 | P2 — fora deste pacote (empty em campanhas feito como bônus mínimo) | Parcial |

## Objetivos do brief (mapeamento)

| Pedido | Como foi atendido |
|--------|-------------------|
| Navegação única | Settings no Mais; labels rail/página alinhados |
| Glossário definitivo | Resultado, Novo ponto, Financeiro, Regras, Atalhos |
| Mapa conforme auditoria | Labels + CTAs sem emoji principais + JS |
| CRM integrado | Labels de regras + confirm convert |
| Relatórios reorganizados | Hub honesto de atalhos |
| Configurações em Mais | QW-01 |
| Quick Actions | Period seg + overflow no dashboard |
| Padronização visual | Overflow CSS, filtros, headers, sem emoji |

## Fora de escopo (ainda no roadmap 8.2.4)

- Shell thrash create/edit (Fase B)
- Migração total Team CSS / CRM design system (Fase C)
- Unificar Sales App + Mapa (Fase D)
- Relatórios com gráficos reais / bulk approve (Fase E)

## Testes

```
.\.tools\php\php.exe artisan test --filter=Sprint825
```

Ver também regressão Sprint822 / Maps / Commissions.

## Artefatos

- [CHANGELOG.md](./CHANGELOG.md)
- [screenshots.md](./screenshots.md)
- Canvas: `sprint-825-quick-wins.canvas.tsx`
