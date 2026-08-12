# SAFE-AREA

## Tokens (`client-ui.css` + `seller-app.css`)

```
--safe-area-top / right / bottom / left
= env(safe-area-inset-*, 0px)
```

## Aplicação

- `.op-mobile-bar` — altura e padding-top incluem inset top.
- `.op-toast` e `#next-house-wrap` — inset bottom.
- `#map-page header.map-toolbar` — padding-top + safe-area.
- Sales App já usava `--safe-bottom` + `viewport-fit=cover`.
- Shell Capacitor: padding da `<main>` com os quatro insets.

## Viewport

Operacional e shell: `width=device-width, initial-scale=1, viewport-fit=cover`.

`layouts.app` (admin) não recebeu `viewport-fit=cover` nesta sprint (QA web).

## Status bar

Plugin `@capacitor/status-bar` **não** instalado. Estratégia futura: alinhar `theme-color` / overlay ao tema dark (`#0F1117`). Documentado para 8.2.33+.
