# LUCIDE

## Pin

`lucide@0.469.0` (mesma versão do UMD unpkg).

## Contrato preservado

```js
window.lucide = lucide;
lucide.createIcons(); // data-lucide
```

`seller-app.js` pinta ícones no load e em `DOMContentLoaded` via `createIcons({ icons })`.

O UMD da CDN registrava o set automaticamente; o ESM **exige** `{ icons }`. Sem isso, `createIcons()` lança e o try/catch da 8.2.32 deixava o rail sem SVG (hotfix 8.2.32.1).

`window.lucide.createIcons()` permanece compatível (wrapper injeta `icons` + `stroke-width: 2`).

Chamadas existentes em `operational-map.js`, `client-mobile.js` e blades continuam válidas.

## Onde

- Operacional: CSS + JS vendor.
- `layouts.app`: só JS vendor (ícones).
- Sales App: não usava Lucide CDN.
