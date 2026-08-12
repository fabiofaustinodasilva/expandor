# Hotfix — Capacitor CSP vs EXPANDOR_API_BASE

## Causa

`script-src 'self'` bloqueava o `<script>` inline que definia `window.EXPANDOR_API_BASE`.
No APK (Chrome remote debugging): `EXPANDOR_API_BASE === undefined` e o guard tratava a API como ausente.

`CAP_API_URL` / prepare estavam corretos; o valor existia no HTML, mas o browser não executava o inline.

## Correção

- Gerar `public/capacitor-shell/runtime-config.js` (arquivo externo local).
- `index.html` carrega `./runtime-config.js` **antes** de `./vendor/seller-app.js`.
- CSP permanece `script-src 'self'` — sem `unsafe-inline`, sem `*`, sem `eval`.
- `connect-src 'self' {origin da CAP_API_URL}`.

## frame-ancestors

Diretiva `frame-ancestors` é **ignorada** quando o CSP vem de `<meta http-equiv>` (aviso do Chrome).
Removida do meta do shell para eliminar o warning falso. Proteção de framing continua relevante em respostas HTTP do backend/web; o shell Capacitor é asset local.

## Rebuild

```bash
set CAP_API_URL=https://expandor.unicanetwork.com.br
npm run build
npx cap sync android
```

Confirmar:
- `android/app/src/main/assets/public/runtime-config.js`
- `window.EXPANDOR_API_BASE = "https://expandor.unicanetwork.com.br"`
- Sem script inline de config no `index.html`
