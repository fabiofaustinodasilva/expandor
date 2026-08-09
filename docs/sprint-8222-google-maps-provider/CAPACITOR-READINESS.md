# CAPACITOR-READINESS — Sprint 8.2.22

## O que esta sprint prepara
- Adapter de basemap (`ExpandorMapProvider`) independente do domínio comercial
- Resolução de provider no backend por tenant
- Fallback Leaflet se Google falhar

## O que NÃO fazer depois
- Reutilizar a mesma browser key (HTTP referrer) no Android/iOS
- Embutir key em binário sem restrição de app

## Chaves futuras
| Plataforma | Restrição típica |
|------------|------------------|
| Web | HTTP referrers |
| Android | package name + SHA-1 |
| iOS | bundle ID |

## Nota
GoogleMutant / Maps JS no WebView pode exigir configuração adicional; app nativo pode preferir SDK nativo Google Maps. Domínio comercial Expandor deve continuar desacoplado do engine.
