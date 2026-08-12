# CSP

Meta CSP no shell (`scripts/prepare-capacitor-shell.mjs`):

```
default-src 'self';
script-src 'self';
style-src 'self' 'unsafe-inline';
img-src 'self' data:;
connect-src 'self' {CAP_API_URL|APP_URL origin};
```

Não abre `script-src *` nem `connect-src *`.

Google Maps continua separado (web operacional / 8.2.32). O shell de login desta sprint **não** carrega Maps.

`CAP_API_URL` / `APP_URL` no ambiente de build definem o `connect-src`. Sem URL, só `'self'` (login falhará até configurar a API).
