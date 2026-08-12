# Test report 8.2.34

Arquivo: `tests/Feature/Release/Sprint8234SellerMobileApiTest.php`

Resultado local: **10 passed (120 assertions)**.

Cobertura:

1. bootstrap público  
2–3. markers + bbox  
4–6. list / search / detail  
7–13. create point, visit, interested, follow-up, sale, sale item, commission awarded  
14–16. products sellable, agenda today, commissions self  
17–18. cross-tenant + seller-only  
19–20. device binding + session replaced  
21. validation_error  
22. N+1 markers  
23–34. LocationService, Capacitor adapter, API client, MapAdapter, GPS errors, nav, create/visit/sale/reward, offline mutation, SecureAuthStorage inalterado  

Regressão local (180 passed / 1240 assertions no filtro combinado): Sprint8233, 8232, 8231, 8230, 8230.1, 8229, 8228, 8226, 8225, Maps, SalesApp, PilotSeller, Agenda, Products sellable, SalesCommission, MobileApi.

Migrations 8.2.34: zero.
