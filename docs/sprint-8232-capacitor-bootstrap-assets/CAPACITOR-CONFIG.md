# CAPACITOR-CONFIG

Arquivo: `capacitor.config.json`

(CLI 7: `capacitor.config.ts` exige TypeScript compatível com o loader CommonJS; `capacitor.config.js` ESM com `"type": "module"` não popula `appId`. JSON é o formato estável nesta stack.)

| Chave | Valor | Nota |
|-------|-------|------|
| appId | `br.com.expandor.app` | Provisório 8.2.31; domínio/branding Expandor sem objeção |
| appName | `Expandor` | `APP_NAME` / produto |
| webDir | `public/capacitor-shell` | shell buildado, **não** `/public` inteiro, **não** `server.url` de produção |

## Dev live reload

Não há `server.url` commitado. Para live reload local, adicionar **temporariamente** (não commitar):

```json
"server": { "url": "http://192.168.x.x:5173", "cleartext": true }
```

Ver `.env.example`. Nunca hardcode de produção.

## webDir / segurança

O shell contém só `index.html` + vendor IIFE. Sem `.env`, sem `storage`, sem keystore. `public/` completo **não** é empacotado (evitar copiar secrets/uploads).
