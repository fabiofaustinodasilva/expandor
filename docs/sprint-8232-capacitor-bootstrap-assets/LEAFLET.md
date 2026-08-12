# LEAFLET

## Pin

`leaflet@1.9.4` — sem major bump (compatível com MarkerCluster 1.5.3, GoogleMutant 0.14.1, house pins 8.2.29).

## Globals

```js
import L from 'leaflet';
window.L = L;
```

`operational-map.js` e `map-provider.js` seguem usando `L.map`, `L.marker`, etc.

## CSS / imagens

CSS via `seller-app.css`. Marker default PNG/shadow empacotados pelo Vite (`L.Icon.Default.mergeOptions`). House pins são SVG custom — não dependem do PNG default.

## Smoke

`window.ExpandorVendor.leaflet === true` no shell.

Mapa funcional offline **não** é objetivo. Tiles OSM/Esri continuam remotos.
