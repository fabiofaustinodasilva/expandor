# ARCHITECTURE-DECISION

## Opções

| | A — WebView remoto | B — Frontend local + API | C — Híbrido progressivo |
|--|--------------------|--------------------------|-------------------------|
| O que é | Capacitor abre `https://app.expandor…` | UI empacotada no binário, Laravel só API | Shell nativo + API; UI seller migrada em fatias; assets locais cedo |
| Offline | Quase zero (CDN + HTML remoto) | Possível | Possível após 8.2.35 |
| Update loja | Web muda sem binário | Binário ou OTA | Web para o que ainda for hospedado; binário para shell/plugins |
| Segurança | Cookie 3rd-party no WebView | Bearer em Keychain | Bearer device-bound |
| Manutenção | Uma UI | Duas UIs até Blade sair | Uma UI operacional, API compartilhada |
| Mapas | CDN + tiles online | Bundle Leaflet + tiles online | Idem B, tiles nunca “offline mágico” |
| Sessão | CSRF + SameSite=lax **quebra** | Precisa fechar gap PAT | Fecha gap na 8.2.33 |

## Recomendação: **C**

Motivos no código real, não conveniência:

1. Cookie `SameSite=lax` + CSRF do mapa **não** são first-party em `capacitor://` / `https://localhost`.
2. Tailwind Play CDN + unpkg Leaflet **bloqueiam** WebView/offline.
3. Já existe `/api/mobile/v1` e `/api/v1/maps/markers` — base para B/C, insuficiente para A “só wrap”.
4. Já existe fila offline web (`field-offline-queue.js`) amarrada a CSRF — precisa virar API + idempotency, não WebView.
5. Seller é Blade denso; B completo nesta sprint seria redesign. C fatia: 32 assets → 33 auth nativo → 34 GPS → 35–36 offline.

**A (remoto) só como smoke interno** depois de empacotar CDN. **Não** é o app da Play/App Store.

**B puro** exige reescrever mapa/agenda/clientes/comissão agora — fora de escopo.

## Stack alvo (MVP loja)

```
Capacitor (Android/iOS)
  → shell (splash, status bar, back, geolocation, secure storage)
  → WebView com assets LOCAIS (CSS/JS/Leaflet/Lucide)
  → HTTPS Laravel
       /api/mobile/v1  (seller)
       /api/v1         (markers/branding)
  → Bearer Sanctum device-bound + session_version
```

Admin/Manager continuam **web**. App = **Seller**.
