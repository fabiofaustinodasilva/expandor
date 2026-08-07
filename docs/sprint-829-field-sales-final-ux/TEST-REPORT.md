# TEST-REPORT — Sprint 8.2.9

## Relacionados ao mapa / Sales

```
.\.tools\php\php.exe artisan test --filter="Sprint829|Sprint828|Sprint827|PilotSellerUx|FirstApproach|MapsModule|SalesApp"
```

**Resultado: 39 passed / 0 failed** (270 assertions)

## Suíte completa

```
.\.tools\php\php.exe artisan test
```

**Resultado: 473 passed / 4 failed** (2791 assertions)

## Falhas restantes (fora do escopo mapa)

Mesmas da 8.2.8 — **não mascaradas**:

| Teste | Motivo aparente |
|-------|-----------------|
| `SalesCommissionModuleTest` (2) | UI/comissões — não toca `operational-map.js` |
| `TeamHubTest` (1) | Hub de equipe / nav |
| `VisitHistoryTest` (1) | Histórico espera “Rota” — shell/nav legado |

## Cobertura Sprint829

- Meu Local → `locateMyPosition` (não cria)
- Minha localização → locate only
- Toque → form direto / sem Casa sem cadastro
- Toast + skip adjust seller
- GPS messages
- Voltar ao mapa + breakpoints
- Anti duplo envio
- Sem admin no mapa
