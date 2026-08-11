# FRONTEND-DATES (final)

## Contrato

1. **Instants:** backend formata string BRT (`d/m/Y H:i`) via `AppTime` — frontend **não** reconverte.
2. **Follow-up:** inputs `date`/`time` no browser → string sem offset → `parseWall` no backend.
3. **Mapa `nowLabel()`:** `new Date().toLocaleString('pt-BR', …)` — label UX local do browser; metadados oficiais de visita vêm do servidor.

## Onde há `Date` / `toLocaleString` (mapa)

- `combineFollowUpAt` / `localDatePlusDays`: montam `Y-m-d` / `Y-m-dTH:i` sem Z.
- `nowLabel()`: browser local.
- `toLocaleString` também usado para **moeda** (não data).

## Evitar

Backend BRT + `new Date(isoComZ)` no JS (dupla conversão). Payload de follow-up **não** usa ISO com Z.
