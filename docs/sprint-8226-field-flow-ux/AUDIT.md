# AUDIT — Sprint 8.2.26 Field Flow UX

## Causas raiz

### 1. Novo Ponto cortado
`.map-sheet-panel` é flex column com `max-height` + `overflow:hidden`, mas **sem `min-height: 0`**. Como flex item do overlay (`#point-modal.open { display:flex }`), o `min-height:auto` padrão faz o painel crescer com o conteúdo → o body não limita altura → `overflow-y:auto` não engaja → footer/conteúdo são **clipados**.

### 2–3. Inconsistências Visita / Venda
Chrome já é o mesmo (header/body/footer 8.2.23.1). Diferenças: títulos, CTAs, conteúdo. **Confirmar venda não é modal separado** — é `#visit-sale-finalize` / `#point-sale-finalize` dentro do mesmo sheet.

### 4. Scroll falha
Mesma causa do item 1: body scrollável só funciona quando o painel tem altura limitada (`min-height:0` no flex child).

### 5–6. “Nenhuma residência nesta área”
- HTML `#map-empty-state` (card central, `pointer-events-auto`)
- JS `renderMarkers()` quando `filtered.length === 0 && initialFitDone`
- Dispara a cada `loadMarkers` após pan/zoom (moveend debounce 400ms) → flicker
- Tailwind `.hidden` vs `.visible` pode ainda mascarar; quando aparece, **parece modal** e sugere GPS obrigatório

### 7. Duplicação
Dois sheets paralelos (point/visit) + sale-finalize ×2 + chips outcome ×2. Unificar **padrão visual**, não merge forçado de forms.

## Script
`operational-map.js?v=56` (será 57 nesta sprint).
