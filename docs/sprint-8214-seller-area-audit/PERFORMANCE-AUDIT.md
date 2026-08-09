# PERFORMANCE-AUDIT

Somente análise — sem otimizar.

## Mapa

- Leaflet + markercluster via CDN unpkg  
- `operational-map.js` monolítico (v49+)  
- Markers API por bbox/filtro  
- Offline queue (`field-offline-queue.js`)  

## Apresentação

- Catálogo JSON **uma vez** no GET present (bom)  
- Imagens `loading="lazy"`  
- Vídeo sob demanda  

## Riscos 4G ruim

| Gargalo | Impacto |
|---------|---------|
| CDN Leaflet/Tailwind/Lucide | First paint mapa |
| Imagens produto grandes | Deck lento |
| Reload completo ao Contratar (nova navegação mapa) | 1–3s percebidos |
| Sales App + rail = duas UIs | Cache/assets duplicados conceitualmente |

## Quick wins futuros

- Prefetch catálogo no mapa (idle)  
- Service worker / cache tiles (avaliar)  
- Contratar sem full navigation (modal in-place) — maior esforço
