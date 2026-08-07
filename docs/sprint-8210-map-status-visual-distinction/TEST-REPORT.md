# TEST-REPORT — Sprint 8.2.10

## Relacionados (após hotfix mapa em branco)

```
.\.tools\php\php.exe artisan test --filter="Sprint8210|Sprint829|Sprint828|Sprint827|MapsIndex|FirstApproach|PilotSeller|SalesApp"
```

**40 passed / 0 failed** (270 assertions)

Inclui regressão: container `#operational-map`, scripts Leaflet/provider/`operational-map.js?v=46`, listener `citySelect`, distinção return/no_interest.

## Suíte completa (baseline 8.2.10)

```
.\.tools\php\php.exe artisan test
```

**482 passed / 4 failed** (2838 assertions) — baseline anterior à hotfix; falhas conhecidas abaixo.

## Falhas restantes (fora do escopo)

Iguais às sprints anteriores — **não mascaradas**:

- `SalesCommissionModuleTest` ×2
- `TeamHubTest` ×1
- `VisitHistoryTest` ×1
