# TEST-REPORT — Auditoria 8.2.14 seller

## Comando executado

```bash
.\.tools\php\php.exe vendor\bin\phpunit --filter "Sprint8215|Sprint8213|Sprint8212|Sprint8211|Sprint8210|MapsModule|PilotSeller|SalesAppModule|ManagerNav" --testdox
```

## Resultado

**OK — 56 tests, 433 assertions**

Inclui: Maps, PilotSeller, SalesApp, ManagerNav, Sprint 8210–8215.

## Falhas pré-existentes (não mascaradas; fora deste filtro)

- TeamHubTest (título “Equipe Comercial”)  
- SalesCommissionModuleTest (parcial)  
- VisitHistoryTest (parcial)  

## Nota

Auditoria **não** alterou testes de comportamento. Suites acima só para baseline de regressão dos fluxos seller já entregues.
