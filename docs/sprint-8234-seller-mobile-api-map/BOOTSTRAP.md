# Bootstrap

`GET /api/mobile/v1/bootstrap` é o primeiro fetch após login/me.

Inclui só o necessário para montar o shell:

- identidade (user/company/role/permissions)
- feature flags públicas do tenant
- mapa lógico (`MapFrontendConfigBuilder`)
- timezone de display (`AppTime`)
- capabilities: gps=true, offline/sync/push/presentation=false
- session (device/version) já usado em 8.2.33

Não inclui chaves de servidor, SMTP, gateway, `APP_KEY` ou credenciais de tenant.
