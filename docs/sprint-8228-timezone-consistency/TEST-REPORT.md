# TEST-REPORT — 8.2.28 (fechamento)

## Suite nova

`Sprint8228TimezoneConsistencyTest` — **14 passed** (incl. fronteira 22:30 BRT / 01:30 UTC).

## Testes 8216 / 8217 (timezone-dependent → normalizados)

| Suite | Situação |
|-------|----------|
| **8216** | Antes: “hoje” via `now()` UTC do runner. Depois: `freezeOperationalClock` em `America/Sao_Paulo` + wall follow-ups. **Produto não revertido.** |
| **8217** | “Visitas hoje”: freeze BRT + `visited_at` como instante UTC (`->utc()` a partir do horário local). |

## SalesCommission (não temporal)

Falhas de título eram **C) UI/copy legado**: Blade usa `Comissão`/`Financeiro`, não `$pageTitle`. Asserts alinhados. **Não é regressão de timezone.**

## Regressão final (fechamento)

| Suite | Resultado | Classe |
|-------|-----------|--------|
| Sprint8228TimezoneConsistencyTest | PASS | — |
| Sprint8216SellerFieldQuickWinsTest | PASS | B corrigido |
| Sprint8217TeamPresenceActivityTest | PASS | B corrigido |
| AgendaFollowUpsTest | PASS | — |
| MapsModuleTest | PASS | — |
| AnalyticsDashboardTest | PASS | — |
| SalesAppModuleTest | PASS | — |
| SalesCommissionModuleTest | PASS | C copy (asserts) |
| Sprint8225SingleDeviceSessionTest | PASS | — |
| Sprint8224TransactionalEmailPasswordResetTest | PASS | — |
| Sprint8226FieldFlowUxTest | PASS | — |
| Sprint8223CommissionRewardHotfixTest | PASS | — |

Classificação de falhas: **A** regressão 8228 · **B** teste timezone-legado · **C** pré-existente/não temporal.
