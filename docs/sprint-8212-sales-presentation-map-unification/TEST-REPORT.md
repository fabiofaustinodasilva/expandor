# TEST-REPORT — Sprint 8.2.12

## Comando

```bash
.\.tools\php\php.exe vendor\bin\phpunit --filter "Sprint8212|Sprint8211|Sprint8210|Sprint829|Sprint828|Sprint827|Sprint825|Maps|FirstApproach|PilotSeller|SalesApp|ManagerNavCommissions" --testdox
```

## Resultado

**OK — 80 tests, 518 assertions** (2026-08-08)

## Sprint8212SalesPresentationTest

- vendedor abre apresentação e vê Voltar ao mapa
- somente produtos ativos da própria empresa
- vendedor não cria/edita/toggle
- empresa vê + Novo produto
- empresa cria/edita/toggle
- mapa expõe CTA Apresentar produtos + GPS
- Configurações → Produtos

## Regressão incluída

Sprint8211, Sprint8210, Sprint829, Sprint828, Sprint827, Sprint825, Maps, FirstApproach, PilotSeller, SalesApp, ManagerNavCommissions — todos OK.

## Ajuste de regressão 8211

`sales-app.products.show` agora redireciona para o deck de apresentação; o teste 8211 foi alinhado a esse fluxo (sem mascarar falha — mudança intencional da 8.2.12).

## Falhas conhecidas fora de escopo (não mascaradas)

- SalesCommissionModuleTest (2)
- TeamHubTest
- VisitHistoryTest
