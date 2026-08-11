# FUTURE-TENANT-TIMEZONE

## Agora (8.2.28)

Timezone global de display: `APP_TIMEZONE` / `config('app.display_timezone')`.

`company_settings.timezone` já é provisionado como `America/Sao_Paulo` em alguns fluxos, mas **não** é lido pelo runtime de Carbon.

## Futuro SaaS

1. Persistir `companies.timezone` (IANA) por tenant.
2. Resolver `AppTime::zone()` via `TenantContext` → company → fallback `APP_TIMEZONE`.
3. Manter storage UTC para instants.
4. Follow-ups: decidir se wall continua no TZ da empresa ou vira UTC verdadeiro (com migration).

**Não** implementar por empresa nesta sprint.
