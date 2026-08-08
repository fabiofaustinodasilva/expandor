# TEST-REPORT — Sprint 8.2.11

## Relacionados

```
.\.tools\php\php.exe artisan test --filter="Sprint8211|Sprint8210|Sprint829|Sprint828|Sprint827|Sprint825|FirstApproach|PilotSeller|MapsModule|SalesApp"
```

**63 passed / 0 failed**

Inclui GPS único, cores MapMarkerColor, comissão, produtos Sales App e regressão 8.2.5–8.2.10.

## Falhas conhecidas (fora do escopo)

Suíte completa histórica — **não mascaradas**:

- `SalesCommissionModuleTest` ×2
- `TeamHubTest` ×1
- `VisitHistoryTest` ×1

## Migration local

```
php artisan migrate --path=database/migrations/2026_08_07_210000_add_presentation_fields_to_products_table.php
```

Nota: `migrate` completo no ambiente local pode falhar em migration antiga de unicidade de documento (integridade) — independente desta sprint.
