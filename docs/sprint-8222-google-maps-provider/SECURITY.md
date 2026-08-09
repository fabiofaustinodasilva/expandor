# SECURITY — Sprint 8.2.22

## Browser key
- Não é segredo server-side, mas:
  - só no tenant atual
  - só se entitled + connected + key presente
  - nunca server secret
  - nunca key de outro tenant
  - não em cache Redis do resolver (key anexada live pelo `MapFrontendConfigBuilder`)
  - não em logs / audit (sanitização `AIza…` → `[redacted]`)

## HTML
- Key aparece apenas no `src` do Maps JavaScript API quando `usesGoogleVisual()`
- Free / não configurado / desconectado / downgrade: sem script Google e sem `AIza` no HTML

## Endpoint fallback
- `POST /map/provider-fallback`
- Auth + `maps.view`
- Throttle `20/min`
- Payload: `code`, `message` sanitizados
- Sem aceitar API keys arbitrárias

## Test connection
- Tenant + policy + entitlement (8221)
- Não é sandbox para chaves de terceiros sem autorização
- Não loga key

## Cross-tenant
Builder / HTML só usam `company_id` da sessão autenticada.
