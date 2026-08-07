# Sprint 8.2.10 — Diferenciação visual dos status do mapa

## Objetivo

Fazer **Retorno** e **Sem interesse** visualmente distintos no mapa, sem mudar regras de negócio, GPS, Meu Local ou formulário (fluxo 8.2.9 intacto).

## Fluxo de cor (encontrado)

```
PropertyStatus (domínio)
  → MapCommercialGroup::fromStatus()   // filtros / commercial_group
  → MapMarkerColor::forStatus()        // cor do pin (8.2.10 por status)
  → MapQueryService::toMarker()        // payload color + commercial_group
  → operational-map.js coloredIcon()   // render + marca R / ×
  → MapCommercialGroup::legend()       // legenda Blade
```

## Decisão

- `commercial_group=visited` **mantido** para ambos (filtros iguais).
- Cores distintas via `MapMarkerColor::forStatus`.
- Marca secundária: **R** (retorno) e **×** (sem interesse).

## Artefatos

- [AUDIT.md](./AUDIT.md)
- [STATUS-MAPPING.md](./STATUS-MAPPING.md)
- [UX-CHECKLIST.md](./UX-CHECKLIST.md)
- [TEST-REPORT.md](./TEST-REPORT.md)
- [CHANGELOG.md](./CHANGELOG.md)

## Branch

`feature/sprint-8210-map-status-visual-distinction`
