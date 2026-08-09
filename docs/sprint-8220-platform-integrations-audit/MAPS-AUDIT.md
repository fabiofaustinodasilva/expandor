# MAPS-AUDIT

## Respostas obrigatórias

### 1. Engine
**Leaflet 1.9.4** (+ leaflet.markercluster 1.5.3) via unpkg CDN em `resources/views/maps/index.blade.php`.

### 2. Tile providers
| Modo | Provider | URL |
|------|----------|-----|
| Rua | OpenStreetMap | `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png` |
| Satélite | Esri World Imagery (provisório) | `server.arcgisonline.com/.../World_Imagery/...` |

Definidos em `public/js/map-provider.js`.

### 3. Rua
`ExpandorMapProvider` cria `L.tileLayer` OSM e adiciona ao mapa; `#basemap-street` chama `setBasemap('street')`.

### 4. Satélite
Troca layer para Esri; toast avisa “provisório… Google/Mapbox virá depois”. Não é Google.

### 5. Onde URLs estão definidas
Hardcoded em `public/js/map-provider.js` (+ fallback OSM em `operational-map.js` se adapter ausente).

### 6. Telas que dependem
**Uma** UI Leaflet: `GET /map` (`map.index`).  
API markers: `/api/v1/maps/markers`.  
Links externos Google Directions em follow-ups/my-visits (não engine).

### 7. Manager e seller compartilham provider?
**Sim.** Mesma view/scripts. Diferença: UX (`data-is-field-seller`), attribution/zoom ocultos no seller.

### 8. Como trocar provider hoje?
Só no código: `ExpandorMapProvider.create(map, { provider: 'leaflet_osm' })` hardcoded.  
`company_settings.map_provider='leaflet'` é gravado no provision e **ignorado** em runtime.

### 9. O que mudaria para provider dinâmico?
1. `IntegrationResolver` server → injetar `data-map-provider` + config pública no Blade  
2. Adapter `google` (ou outro) em `map-provider.js`  
3. Fallback se falhar  
4. **Não** alterar MapQueryService / filtros comerciais / clusters (camada Expandor)

## CSP
Nenhuma CSP no app hoje. Futuro: permitir hosts de tiles/CDN Google/OSM/Esri.

## Attribution
Manager: OSM/Esri. Seller: attribution removida (decisão UX campo; revisar ToS se Google).

## Compatibilidade com mapa atual (8.2.19)
Superfície simplificada preservada. Integração futura deve plugar no adapter, sem redesenhar chrome.
