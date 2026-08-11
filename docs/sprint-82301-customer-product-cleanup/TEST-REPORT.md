# TEST-REPORT — 8.2.30.1

## Suite nova

`tests/Feature/Release/Sprint82301CustomerProductCleanupTest.php`

**OK (13 tests, 67 assertions)**

1. lista desktop (`data-customer-list`)
2. mobile primitiva (`client-data-table--responsive`)
3. busca preservada
4. cliente sem histórico → soft-delete
5. cliente com visita → bloqueio; visita permanece
6. cliente com venda → bloqueio; sale permanece
7. ponto/endereço não hard-deleted
8. seller não exclui
9. cross-tenant 404
10. produto nunca usado → delete
11. produto com venda/comissão → bloqueio; snapshots permanecem
12. desativar some de `sellableOptions`
13. filtro Ativos / Desativados / Todos
14. ProductPolicy + tenancy
15. zero migrations

## Regressão

Customers, SalesCommission, 8223 + hotfix + 82231, 8230, SalesApp, PilotSeller, Maps, Campaigns, ProductCatalog, 8215 Products, Properties, Visits.

**OK (115 tests, 698 assertions)**
