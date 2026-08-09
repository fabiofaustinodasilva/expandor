# TEST-REPORT — Sprint 8.2.18

## Suite nova

`Tests\Feature\Release\Sprint8218SmartTerritoryCampaignTest` — **16 passed**

| # | Cobertura | Resultado |
|---|-----------|-----------|
| 1 | Campanha todos os setores (pivot vazio) | OK |
| 2 | Um setor | OK |
| 3 | Vários setores | OK |
| 4 | Setores filtrados pela cidade (endpoint) | OK |
| 5 | Cidade incompatível rejeita setor | OK |
| 6 | Edição carrega seleção | OK |
| 7 | Campanha legada (= cidade inteira) | OK |
| 8 | Mapa cidade inteira | OK |
| 9 | Mapa setores específicos | OK |
| 10 | Seller vê território permitido | OK |
| 11 | Seller/admin não vê setor outro tenant | OK |
| 12 | Admin não associa setor cross-tenant | OK |
| 13 | Setor personalizado | OK |
| 14 | GPS first-approach sem regressão | OK |
| 15 | Apresentação/Contratar sem regressão | OK |
| 16 | Form UX modos território | OK |

## Regressão

Comando:

```
php artisan test --filter="Sprint8217TeamPresenceActivityTest|Sprint8216SellerFieldQuickWinsTest|Sprint8215ProductsPrimaryNavTest|Sprint8213SalesPresentationContractTest|MapsModuleTest|CampaignsModuleTest|PilotSellerUxTest|SalesAppModuleTest"
```

**50 passed** (402 assertions). Nenhuma falha pré-existente mascarada.
