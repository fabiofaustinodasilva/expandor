# TAILWIND

## Antes

`layouts.operational` carregava **Tailwind Play CDN** (`cdn.tailwindcss.com`). `package.json` já tinha Tailwind 4 + `@tailwindcss/vite`, usados só no welcome (`resources/css/app.css`).

## Depois

`resources/css/seller-app.css`:

- `@import 'tailwindcss'`
- `@source` blades, JS de resources e `public/js/**/*.js`
- system font stack (sem Google Fonts)
- tokens `--safe-area-*`

Compilado no IIFE (`vite.seller.config.js`) → `public/vendor/expandor/seller-app.css`.

## Escopo

Local no seller/operacional (mapa, agenda, clientes, comissão, perfil, rail). `layouts.app` **não** inclui o CSS (evita Preflight no admin custom). `sales-app` já era CSS local.

## Safelist

Nenhuma gigante. Classes dinâmicas do mapa são string literals em `public/js/operational-map.js` e entram no `@source`.

## Risco

Play CDN era JIT v3-like; o build é Tailwind **4**. Utilities usadas no Blade devem ser geradas pelo content scan. Se alguma classe dinâmica concatenada faltar, adicionar safelist pontual — não feito nesta sprint.
