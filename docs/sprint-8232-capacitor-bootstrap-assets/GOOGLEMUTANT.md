# GOOGLEMUTANT

## Pin / origem

npm `leaflet.gridlayer.googlemutant@0.14.1`  
(adapter Leaflet ↔ Google Maps JavaScript API)

**Não** usa tile URLs Google não oficiais.

## Local vs remoto

| Peça | Onde |
|------|------|
| Adapter/plugin | local (bundle seller) |
| Maps JavaScript API | remoto oficial `maps.googleapis.com/maps/api/js` **somente** se `usesGoogleVisual()` |

O adapter fica disponível no bundle mesmo sem key/backend (`window.ExpandorVendor.googleMutant`). Inicializar camada Google continua exigindo o script oficial + key de browser em runtime — nunca hardcoded.

## Versão no DOM

`data-googlemutant-version="0.14.1"`
