# Sprint 8.2.23 — Comissões 2.0

Produto passa a ter comissão **fixa** ou **percentual**. Vendas field-sales (`installation_requested`) persistem snapshot e nunca recalculam histórico.

## Escopo

- Product CRUD (tipo + valor/%) + preview
- Cálculo no Contratar / FirstApproach
- Snapshot em `sale_items` / `sales_commissions`
- Feedback seller (toast + som) se comissão > 0
- Testes `Sprint8223CommissionsV2Test`

## Fora de escopo (v1)

- Unificar com CRM `CommissionRule`
- Cancelamento/estorno novo
- Capacitor / haptic
- Preferências avançadas de som (mínimo / documentado)
- Negociação que altere unit_price no request (mantém preço catálogo × qty → `line_total`)

## Branch

`feature/sprint-8223-commissions-v2` — sem push / sem merge.

## Docs

| Arquivo | Conteúdo |
|---------|----------|
| [AUDIT.md](./AUDIT.md) | 30 perguntas |
| [HOTFIX-REWARD.md](./HOTFIX-REWARD.md) | Diagnóstico + hotfix da recompensa |
| [DATA-MODEL.md](./DATA-MODEL.md) | Schema |
| [CALCULATION-RULES.md](./CALCULATION-RULES.md) | Fórmulas + arredondamento |
| [RULE-PRECEDENCE.md](./RULE-PRECEDENCE.md) | Precedência auditada |
| [SNAPSHOT.md](./SNAPSHOT.md) | Snapshot financeiro |
| [SELLER-REWARD-UX.md](./SELLER-REWARD-UX.md) | Toast + som |
| [SECURITY.md](./SECURITY.md) | Policies / tenancy |
| [MIGRATION-NOTES.md](./MIGRATION-NOTES.md) | Migração legado |
| [TEST-REPORT.md](./TEST-REPORT.md) | Testes |
| [UX-CHECKLIST.md](./UX-CHECKLIST.md) | Checklist manual |
| [CHANGELOG.md](./CHANGELOG.md) | Changelog |
