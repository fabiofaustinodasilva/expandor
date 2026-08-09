# RUNTIME-ERRORS — Sprint 8.2.22

## Classificação
| Tipo | Efeito no mapa | Status na Central |
|------|----------------|-------------------|
| Erro transitório de runtime (timeout, script, billing momentâneo) | Fallback Leaflet + toast | **Não** força disconnect automático |
| Configuração inválida (status `error`, disabled, missing key) | Resolver já devolve Leaflet no preload | Mantém status existente da 8221 |

## Logging
`MapProviderRuntimeReporter`:
- company_id, provider, code sanitizado, message sanitizada
- throttle 1h por `(company_id, code)` via Cache
- nunca API key

## Testar conexão (revisão 8222)
- Continua Geocoding server-side (8221)
- **Limitação**: browser key com HTTP referrer pode falhar no probe; já tratado como `ok_browser_restriction`
- Não fingimos validar Maps JS 100% no servidor
- Confirmação real: carregar `/map` com Google visual; runtime fallback cobre falha

## QA force failure
`/map?force_google_failure=1` apenas em `local`/`testing`.
