# MAP-OPERATION-SHEET

Classes: `map-operation-sheet` / `map-sheet-panel`, `map-operation-header`, `map-operation-body`, `map-operation-footer`.

Regras CSS críticas:
- `min-height: 0` no painel
- body: `flex:1; overflow-y:auto; overscroll-behavior:contain`
- footer: `flex:0; safe-area`
- z-index sheet `100` (acima mobile bar 55)

Atributo: `data-map-operation-sheet="1"`.
