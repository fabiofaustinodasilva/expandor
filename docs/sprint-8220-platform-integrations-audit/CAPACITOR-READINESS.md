# CAPACITOR-READINESS

## Contexto

Expandor Vendas: Capacitor Android/iOS, offline-first (planejado).

## Implicações Google Maps

- WebView pode usar Maps JS com key referrer **e** App restrictions.  
- Nativo: Maps SDK for Android / iOS — **keys e setup distintos**.  
- Config tenant deve syncar: `integration_key` + platform (`web|android|ios`) credentials.  
- Offline: tiles Google geralmente **não** cacheiam livremente (ToS); fallback Leaflet/OSM offline pack ou mapa estático.

## Proposta futura

```
company_integrations
  credentials: {
    web_browser_key,
    android_key?,
    ios_key?,
    server_key?
  }
```

App no boot: baixa config pública (sem server secret) via API autenticada.

## Não fazer agora

Não instalar Capacitor; não SDKs Maps nativos.
