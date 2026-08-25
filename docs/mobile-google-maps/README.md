# Google Maps no app mobile (EXP Vendedor / Capacitor)

O mapa do app de campo usa **Leaflet como host** (marcadores, clustering, clique, GPS) e **Google Maps JavaScript API** via **Leaflet.GoogleMutant** como basemap visual (Mapa / Satélite), alinhado ao provider web (Sprint 8.2.22).

Fallback absoluto: OpenStreetMap (rua) + Esri World Imagery (satélite).

## APIs a habilitar no Google Cloud

1. **Maps JavaScript API** (obrigatória) — basemap roadmap/satellite via GoogleMutant.
2. **Geocoding API** (opcional) — apenas se a empresa usa “Testar conexão” na integração web; não é necessária para o mapa do app.

Não habilitar Places / Directions nesta entrega.

## Onde criar a chave

1. [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials.
2. Create credentials → API key.
3. Restringir a chave (nunca deixar irrestrita em produção).

## Restrições recomendadas (Maps JavaScript API no Capacitor WebView)

O app Capacitor carrega o Maps **JavaScript** API dentro do WebView (não o SDK nativo Android). Por isso a restrição correta é **HTTP referrers**, não package/SHA do Maps SDK.

Inclua na allowlist (ajuste ao ambiente):

| Referrer | Uso |
|----------|-----|
| `https://localhost/*` | Capacitor Android WebView (comum) |
| `capacitor://localhost/*` | Capacitor (alguns builds) |
| `http://localhost/*` | Dev / alguns emuladores |
| `https://SEU-DOMINIO-API/*` | Se o shell for servido via HTTPS remoto |

APIs permitidas na chave: **Maps JavaScript API** (somente o necessário).

### Android (quando migrar para Maps SDK nativo — futuro)

Se no futuro houver `@capacitor/google-maps` nativo:

- Restrição por **Android apps**: package name + SHA-1/SHA-256 do keystore de release/debug.
- Meta-data `com.google.android.geo.API_KEY` no `AndroidManifest.xml`.
- **Não** reutilizar cegamente a mesma chave browser do WebView sem revisar restrições.

### iOS (futuro)

- Bundle ID restriction na chave.
- Projeto `ios/` Capacitor ainda não está no repositório.

## Onde configurar no Expandor

| Camada | Onde |
|--------|------|
| Chave da empresa | Integrações → Google Maps (`browser_api_key` / credentials da `CompanyIntegration`) |
| Entitlement | Feature `google_maps` no plano da empresa |
| Runtime mobile | Bootstrap `GET /api/mobile/v1/...` → `data.map.provider` + `data.map.google.browserKey` |
| Shell Capacitor | CSP em `scripts/prepare-capacitor-shell.mjs` (script-src / img / connect para Google) |

**Nunca** commitar a chave real no Git. Não colocar chave em `capacitor.config.json` nem hardcode no JS.

## Fluxo no app

1. Login → bootstrap retorna config de mapa do tenant.
2. `MapAdapter` inicia com Leaflet + OSM (mapa utilizável imediatamente).
3. Se `provider=google_maps` e há `browserKey`, carrega `maps.googleapis.com` e troca basemap para GoogleMutant.
4. Camadas: **Mapa** = `roadmap`, **Satélite** = `satellite`.
5. Falha de auth/timeout → permanece OSM/Esri (sem quebrar o app).

## Rebuild do shell / APK

Após alterar JS/CSP:

```bash
npm run build:seller   # ou pipeline que gera public/vendor/expandor/seller-app.js
node scripts/prepare-capacitor-shell.mjs
npx cap sync android
```

Novo APK só é necessário para publicar a mudança no dispositivo (esta sprint não publica APK).

## Checklist de segurança

- [ ] Chave com restrição de referrer (não irrestrita)
- [ ] Apenas Maps JavaScript API habilitada na chave
- [ ] Empresa entitled + integração conectada
- [ ] Quotas/billing Google da conta da empresa
- [ ] Fallback OSM verificado com chave inválida
