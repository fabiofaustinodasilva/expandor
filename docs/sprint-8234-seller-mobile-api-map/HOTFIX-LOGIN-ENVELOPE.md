# Hotfix — Login envelope / CAP_API_URL

## Causa confirmada (produção)

1. Parser do source **já** lia `payload.data.token` (não `payload.token`).
2. O APK foi gerado com `window.EXPANDOR_API_BASE = ""` porque `prepare-capacitor-shell.mjs` **não carregava `.env`** e o build rodou sem `CAP_API_URL` no ambiente Node.
3. Com base vazia, o `fetch('/api/mobile/v1/login')` não bate na API de produção → corpo sem envelope → `data.token` ausente → **"Resposta de login inválida."**

Validação curl da API (200 + envelope correto) estava certa; o shell não apontava para ela.

## Correção

- `extractLoginData()` exige `data` + `token` string + `user` + `session`.
- `apiFetch` = Response bruto (contrato A); `mobileApi` devolve envelope completo; callers usam `.data`.
- Prepare carrega `.env` / `.env.local` e **exige** `CAP_API_URL` ou `APP_URL`.
- Guard nativo se `EXPANDOR_API_BASE` vazio.

## Rebuild APK (obrigatório)

```bash
# produção — use o domínio real HTTPS
set CAP_API_URL=https://SEU-DOMINIO.com
npm run build
npm run build:seller
npx cap sync android
```

Confirme em `public/capacitor-shell/index.html` e no asset Android:

`window.EXPANDOR_API_BASE = "https://SEU-DOMINIO.com"`

Não use `http://127.0.0.1` no telefone físico.
