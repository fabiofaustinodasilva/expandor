# HOUSE-PIN-DESIGN

## Renderer

`ExpandorCommercialLayer.pinHtml(color, mark, locationKind, opts)`  
Consumido por `coloredIcon()` em `operational-map.js`.

## Forma

Teardrop/map-pin com silhueta de casa branca no centro (SVG local, sem emoji/CDN).

## Tamanho

- Visual: **28×36** px
- Anchor: `[14, 34]` (ponta no lat/lng)
- Hitbox Leaflet = iconSize (confortável no mobile vs 18×18 antigo)

## Variantes

| Variante | Visual |
|----------|--------|
| Status | fill `--pin-color` + stroke branco |
| Mark R/× | badge canto |
| location_kind adjusted/low_accuracy | stroke azul/amarelo |
| Draft (`opts.draft`) | fill 40% + stroke tracejado cinza |
| Selected | glow sky + scale 1.18 (origem base) |

## GPS

Não usa house pin.
