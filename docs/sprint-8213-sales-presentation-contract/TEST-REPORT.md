# TEST-REPORT — Sprint 8.2.13

## Comando

```bash
.\.tools\php\php.exe vendor\bin\phpunit --filter "Sprint8213|Sprint8212|Sprint8211|Sprint8210|Sprint829|Sprint828|Sprint827|Maps|FirstApproach|PilotSeller|SalesApp" --testdox
```

## Resultado

**OK — 78 tests, 516 assertions** (2026-08-08)

## Sprint8213SalesPresentationContractTest

- apresentação abre com Contratar / Voltar ao mapa
- product_id preservado no deep-link `contract_product`
- mapa/JS: GPS + FirstApproach + seed do produto
- tenancy / produtos ativos
- atalhos Equipe e Financeiro → Produtos

## Regressão

Sprint8212–8210, 829–827, Maps, FirstApproach, PilotSeller, SalesApp — OK.

## Fora de escopo (não mascaradas)

SalesCommissionModuleTest, TeamHubTest, VisitHistoryTest.
