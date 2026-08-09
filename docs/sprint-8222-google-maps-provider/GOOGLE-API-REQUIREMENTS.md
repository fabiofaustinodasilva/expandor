# GOOGLE-API-REQUIREMENTS — Sprint 8.2.22

## APIs necessárias (empresa / Google Cloud)
1. **Maps JavaScript API** — obrigatória para o basemap visual (GoogleMutant).
2. **Geocoding API** — usada apenas pelo “Testar conexão” server-side (probe 8.2.21). Não é Places.

## Não nesta sprint
- Places API / Autocomplete
- Directions / Distance Matrix
- Map ID (vector) — opcional futuro; não exigido pelo Mutant roadmap/satellite atual

## Restrições da chave (orientação UI)
- Tipo: **browser key**
- Restrição: **HTTP referrers** (`https://dominio/*`, local `http://localhost/*` / `http://127.0.0.1/*`)
- Habilitar somente APIs necessárias
- Billing e quotas: responsabilidade da conta Google da empresa

## Capacitor (futuro)
Não reutilizar cegamente a browser key no app nativo.
- Android: package name + SHA-1
- iOS: bundle ID
Ver `CAPACITOR-READINESS.md`.

## Testar conexão
Ver decisão em `RUNTIME-ERRORS.md` / seção Test Connection: Geocoding probe permanece; referrer-restricted keys podem retornar `ok_browser_restriction` e a confirmação real ocorre ao carregar o mapa.
