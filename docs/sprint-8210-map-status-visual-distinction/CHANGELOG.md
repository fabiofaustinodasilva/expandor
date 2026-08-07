# CHANGELOG — Sprint 8.2.10

## Alterado

- `app/Domains/Maps/Enums/MapMarkerColor.php` — cor por status; `markForStatus`; `SLATE`
- `app/Domains/Maps/Enums/MapCommercialGroup.php` — `legend()` com Retorno / Sem interesse
- `public/js/map-provider.js` (v=3) — `colorOf` / `markOf`; clusters sem emoji
- `public/js/operational-map.js` (v=46) — marca no pin; toggle legenda; seller vê legenda; **hotfix**: restaurou `citySelect.addEventListener('change')` (regressão de parse JS que deixava o mapa em branco)
- `resources/views/maps/index.blade.php` — legenda collapsible + CSS posição; cache bust `?v=46`

## Hotfix (mapa em branco)

Após 8.2.10, `/map` renderizava chrome mas sem tiles. Causa: `Unexpected token '}'` em `operational-map.js` — linha do listener de cidade removida por acidente no edit de `pointSubmitting`, deixando um bloco órfão que impedia o parse da IIFE (Leaflet nunca inicializava). Cores/UX 8.2.7–8.2.10 preservados.

## Não alterado

Banco, PropertyStatus, VisitStatus, filtro `visited`, fluxo campo 8.2.9, auth/tenancy/billing.
