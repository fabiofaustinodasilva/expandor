# TEST-REPORT — 8.2.32

## Ambiente

| Item | Valor |
|------|--------|
| OS | Windows 10/11 |
| Node | v24.19.0 (LTS; instalado via winget nesta sprint — não estava no PATH) |
| npm | 11.17.0 |
| PHP | 8.4.22 (`.tools/php`) |
| Capacitor | 7.6.8 (`@capacitor/core` / CLI / android / ios package) |
| Vite | 7.3.6 |
| Tailwind | 4 (local, `@tailwindcss/vite`) |
| Leaflet | 1.9.4 |
| MarkerCluster | 1.5.3 |
| GoogleMutant | 0.14.1 |
| Lucide | 0.469.0 |
| Java para Android | Java 8 em `Program Files (x86)\...\java8path` — **insuficiente** (AGP precisa JDK 17+) |
| JAVA_HOME | unset |
| Android SDK / Studio | não detectados |
| Xcode / macOS | N/A (Windows) |

## npm

```
npm install   → 0 vulnerabilities
npm run build → OK
```

### Tamanhos

| Artefato | Size | gzip |
|----------|------|------|
| `public/build/assets/app-*.css` | 70.41 kB | 13.44 kB |
| `public/build/assets/app-*.js` | 48.62 kB | 18.65 kB |
| `public/vendor/expandor/seller-app.css` | 71.59 kB | 18.33 kB |
| `public/vendor/expandor/seller-app.js` | 563.34 kB | 134.91 kB |

Seller JS grande = Lucide completo + Leaflet + Cluster + Mutant (sem code-split nesta fundação). Marker PNGs inlined (base64).

### Grep no bundle seller

Sem `cdn.tailwindcss`, `unpkg.com`, `jsdelivr`, `cdnjs`, `fonts.googleapis`, `APP_KEY`, `DB_PASSWORD`.

Globals no IIFE: `window.L`, `window.lucide`, `window.ExpandorVendor` (leaflet, markerCluster, googleMutant, lucide).

## cap sync

`npx cap add android` copiou `public/capacitor-shell` → `android/app/src/main/assets/public` e gerou `capacitor.config.json` nativo.

`npx cap sync` implícito no add: **OK**.

## Android build debug

**Não executado.** Motivo: sem JDK 17+ / sem `JAVA_HOME` / sem Android SDK. Não falsificado.

`android/` estruturalmente criado:

- `applicationId` / `namespace`: `br.com.expandor.app`
- `app_name`: Expandor
- wrapper Gradle presente

## iOS

`@capacitor/ios` no package.json. `npx cap add ios` **não** rodou (Windows). Pasta `ios/` ausente.

## PHPUnit

Filtro: Sprint8232, 8231, 8230, 82301, 8229, 8228, 8226, 8225, MapsModule, SalesApp, PilotSeller, 8210, 8222 provider.

Após ajuste de `Sprint8222GoogleMapsProviderTest::test_14` (GoogleMutant local):

`OK (147 tests, 898 assertions)` — PHPUnit 11.5.56 / PHP 8.4.22.

## Migrations

Zero.

## Service worker

Não adicionado.

## Ícone / splash

Capacitor gerou placeholders default (`ic_launcher`). Identidade final **não** aprovada — não inventar logo. Marketplace SVGs não servem de adaptive icon. Splash: `androidx.core:core-splashscreen` default, sem asset de marca.
