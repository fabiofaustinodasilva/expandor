# Hotfix: remover setup/onboarding SaaS obrigatório

## Decisão
Empresas comerciais nascem prontas para uso. O wizard SaaS (`/onboarding/*`) não bloqueia mais o primeiro login.

## Causa do bloqueio (antes)
1. `Company` criada via Platform/Trial sem `onboarding_status` → default DB `pending`
2. `LoginController` redirecionava quem tinha `onboarding.manage` + `needsSaasOnboarding()` para `onboarding.index`
3. Banner não bloqueante "Configuração da conta" via `AppServiceProvider`

Não havia middleware forçando setup em todas as rotas.

## Estratégia (sem backfill destrutivo)
- `Company::needsSaasOnboarding()` sempre `false` → empresas `pending`/`in_progress` entram normalmente
- Novas empresas (platform + trial) nascem com `onboarding_status=completed` + `onboarding_completed_at=now()`
- Redirect de login para setup removido
- GETs de `/onboarding/*` redirecionam para mapa/dashboard
- Colunas preservadas; rotas/código legado mantidos (POST complete/skip ainda funcionam)

## Fora de escopo
Billing, planos, Mercado Pago, mapa, EXP Vendedor (além do redirect de login), remoção de colunas.
