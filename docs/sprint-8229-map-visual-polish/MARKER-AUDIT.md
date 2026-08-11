# MARKER-AUDIT

1. **Onde:** `operational-map.js` → `coloredIcon()` + `renderMarkers()`; cores API `MapQueryService` / `MapMarkerColor`.
2. **Antes:** `L.marker` + `L.divIcon` círculo HTML (não circleMarker para imóveis).
3. **Tipos:** property DivIcon; GPS `circleMarker`; draft adjust DivIcon; selected CSS; cluster bubble.
4. **Status:** `new`, `interested`, `return_later`, `no_interest`, `customer`, `installation_requested`.
5. **Cores:** ver STATUS-VISUAL-MATRIX (fonte `MapMarkerColor`).
6. **Selected:** `.map-marker-selected` scale + glow (cor de status preservada).
7. **GPS:** `showDraftLocationMarker` circleMarker `#0ea5e9`/`#38bdf8`.
8. **Clusters:** `L.markerClusterGroup` + `ExpandorCommercialLayer.clusterIcon` (C/I/V/N).
9. **Performance:** SVG inline ~pequeno reutilizado; sem animação contínua nos pins.
10. **Formato final:** pin casa SVG + fill status + marca R/×.
