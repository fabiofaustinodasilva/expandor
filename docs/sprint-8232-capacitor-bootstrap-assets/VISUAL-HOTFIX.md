# 8.2.32.1 — Seller visual hotfix

## Causa raiz

`lucide` ESM **não** registra ícones no `createIcons()` sem o mapa `{ icons }`.

O UMD da CDN (`unpkg.com/lucide@0.469.0`) fazia isso implicitamente.

O bundle local chamava `lucide.createIcons()` sem argumentos. A API lança:

`Please provide an icons object.`

O `try/catch` em `seller-app.js` engolia o erro → `<i data-lucide>` ficava vazio.

Efeito: rail Seller “simples”, ícones ausentes/pequenos, espaçamento alterado, active state sem o peso visual do ícone. Mapa/Leaflet/clusters não dependem de Lucide, por isso “funcionavam”.

## Tailwind 4 vs Play

Preflight TW4 (`@layer base`: `* { margin:0; padding:0 }`, `svg { display:block }`) é diferente do Play (unlayered, v3-like).

Utilities (`w-5`, `h-5`, `flex-1`) **estão** no bundle. Não era safelist.

Correção: não voltar CDN; `createIcons({ icons })` + pin 1.25rem / stroke 2 no `client-ui.css` (design system 8.2.30).

## CSS load order (final)

1. `seller-app.css` — Tailwind 4 utilities + Leaflet/Cluster CSS (`?v=filemtime`)
2. inline `layouts.operational` — tokens + `.op-rail` 72×52 / radius 14
3. `client-ui.css` — tokens 8.2.30 + Lucide rail/map pin + hover/active
4. `@stack('styles')` — mapa (house pins, sheets)

## Cache

`layouts/partials/seller-vendor.blade.php` usa `filemtime` em `seller-app.css` / `.js`. Rebuild muda `?v=`.

## Não feito

Auth mobile, offline, 8.2.33, migrations, push, merge, CDN.
