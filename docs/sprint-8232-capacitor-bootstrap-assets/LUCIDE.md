# LUCIDE

## Pin

`lucide@0.469.0` (mesma versão do UMD unpkg).

## Contrato preservado

```js
window.lucide = lucide;
lucide.createIcons(); // data-lucide
```

`seller-app.js` pinta ícones no load e em `DOMContentLoaded`. Fallback try/catch — shell não fica em branco se Lucide falhar.

Chamadas existentes em `operational-map.js`, `client-mobile.js` e blades (`if (window.lucide) createIcons()`) continuam válidas.

## Onde

- Operacional: CSS + JS vendor.
- `layouts.app`: só JS vendor (ícones).
- Sales App: não usava Lucide CDN.
