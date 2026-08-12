# SECURITY

- Token nunca em log, HTML, localStorage, query string
- Password nunca auditada
- `session_version` só o servidor incrementa
- `device_id` sozinho não autentica
- Ability mínima `seller-app` (não `*`)
- Seller only via `Role::SELLER`
- Permissions continuam no servidor (`sales_app.access` + policies)
- Audit: `auth.mobile_login_succeeded`, `auth.mobile_logout`, `auth.mobile_session_replaced` — `device_id` SHA-256 truncado
- Login throttle: mesmo `throttle:login` da web (e-mail + IP)
- CORS allowlist; CSP sem `script-src *`
- `/api/v1/auth/login` genérico **não** foi convertido (não é o app Seller)

## Erros mobile

```json
{ "success": false, "message": "...", "code": "...", "errors": {} }
```

Códigos: `invalid_credentials`, `validation_error`, `tenant_required`, `session_replaced`, `unauthenticated`, `forbidden`.
