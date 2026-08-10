# EMPTY-MAP-STATE

Antes: card `#map-empty-state` com “Nenhuma residência…” + GPS obrigatório.

Depois:
- overlay bloqueante removido
- `#map-empty-hint` discreto, `pointer-events:none`
- JS `showEmptyAreaHintOnce()` — no máximo 1× por lifecycle do mapa
- status line continua “Nenhum ponto nesta área.”
- mapa 100% utilizável (pan/zoom/toque)
