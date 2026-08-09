# TEST-REPORT — Sprint 8.2.16

## Suite nova

`tests/Feature/Release/Sprint8216SellerFieldQuickWinsTest.php` — **8 passed**

Coberturas:

1. Retorno exige `follow_up_at`
2. Salvar Retorno cria FollowUp
3. FollowUp com `company_id` correto
4. Retorno aparece na Agenda
5. Filtro Hoje mostra só hoje
6. Futuro não aparece em Hoje
7. Seller não acessa retorno de outro tenant (404 por scope)
8. Chip Hoje com contagem real
9–11. Apresentar → deck + Detalhes/Contratar/mapa
12. Mapa continua home seller
13–16. Mais limpo; Perfil / Academia / Comissão ok

## Regressão

Filtro: `Sprint8216|Sprint8215|Sprint8213|Sprint8212|Sprint8211|Sprint8210|Sprint829|Sprint828|Sprint827|FirstApproach|AgendaFollowUps|PilotSeller`

**Resultado:** 84 passed (624 assertions)

Asserts de cache-bust `operational-map.js?v=49` → `?v=50` atualizados nas sprints anteriores (necessário após bump do JS).

## Falhas pré-existentes (não mascaradas)

`SalesCommissionModuleTest`:
- `test_seller_sees_only_own_commission`
- `test_manager_sees_team_and_can_approve_and_pay`

Falham ao procurar texto `Gestão de comissões` na página — independente desta sprint (copy/UI prévia). Demais testes do módulo de comissão passam.
