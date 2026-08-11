# MAP-GPS

## Mapa hoje

Leaflet host. Default OSM + Esri satélite. GoogleMutant se entitlement + key. House pins + clusters 8.2.29. Fallback OSM em erro Google.

**Offline de tiles: não existe e não será prometido no MVP.**  
Opções futuras: região em cache (grande), fallback “sem mapa / lista de pontos”, Maps nativo (rewrite).

## GPS hoje

`navigator.geolocation.getCurrentPosition` — accuracy high, timeout 15s, maximumAge 5s. Sem watch. Sem Capacitor plugin.

## Interface futura (8.2.34)

```
LocationService.getCurrent()
  web → navigator.geolocation
  native → @capacitor/geolocation
```

Uma chamada. Sem duplicar regra de visita/ponto.

## Permissões (não pedir background)

- Android: `ACCESS_FINE_LOCATION` foreground
- iOS: `NSLocationWhenInUseUsageDescription` — “Para registrar o ponto e a visita no mapa.”

Sem rastrear vendedor em background.

## Compatibilidade

Não alterar `operational-map.js` / `MapMarkerColor` nesta sprint. House pins aprovados.
