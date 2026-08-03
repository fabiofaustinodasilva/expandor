# Map Provider — ponto de troca (Sprint UX 2.7)

## Regra

Não substituir Leaflet/OpenStreetMap nesta sprint.

Separação obrigatória:

```
Map Provider (basemap)
        |
        v
Camadas comerciais Expandor (markers, clusters, filtros, painel)
```

A lógica comercial **não** deve ficar presa ao provedor de mapa.

## Atual

| Peça | Implementação |
|------|----------------|
| Engine | Leaflet 1.9 |
| Ruas | OpenStreetMap tiles |
| Satélite | Esri World Imagery (provisório) |
| Adapter JS | `public/js/map-provider.js` → `ExpandorMapProvider` |
| Camada comercial | `ExpandorCommercialLayer` + `operational-map.js` |

## Toggle Rua / Satélite

Controle na UI do mapa (`#basemap-street` / `#basemap-satellite`).

Satélite atual é **fallback** até integrar Google Maps Platform ou Mapbox.

## Troca futura

1. Implementar adapter em `ExpandorMapProvider.create` para `google` ou `mapbox`.
2. Manter a mesma API: `setBasemap('street'|'satellite')`, `supportsSatellite`.
3. Não alterar `MapQueryService`, filtros comerciais nem clusters — só o basemap.
4. Chave de config sugerida (futuro): `MAP_PROVIDER=leaflet_osm|google|mapbox`.

## Limitações do satélite provisório

- Tiles Esri públicos: adequados para demo/campo, não para SLA comercial.
- Atribuição obrigatória na UI do mapa.
- Zoom/estilo podem diferir do Google/Mapbox.
- Sem traffic, places ou geocoding do provedor.
