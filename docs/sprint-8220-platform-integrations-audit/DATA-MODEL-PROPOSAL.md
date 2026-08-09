# DATA-MODEL-PROPOSAL

**NÃO criar migration nesta sprint.**

## Opção recomendada

### `integration_definitions` (plataforma, seed)

Catálogo global: key, name, category, managed_by (`platform|tenant|hybrid`), min capabilities, docs_url, active.

### `plan` features / flags

Já existentes — ver PLANS-FEATURES.md (`google_maps` catalog + `integrations.google_maps` flag).

### `company_integrations` (tenant)

```
id
company_id          FK tenant
integration_key     string  // google_maps
provider            string  // google_maps_js
enabled             bool
status              enum: disconnected|connected|error|testing
configuration       json nullable   // non-secret
credentials         encrypted json  // server secrets
browser_credentials encrypted json nullable // or separate; mask on read
last_tested_at
last_error          text nullable
created_by
updated_by
timestamps
unique(company_id, integration_key)
```

## Alternativa mínima

Estender padrão `WhatsAppConnection` por integração (tabela dedicada `google_maps_connections`) — menos genérico, mais rápido no MVP Maps.

## Recomendação

Começar genérico `company_integrations` se Central for multi-provider; senão 1 tabela Maps no primeiro slice e generalizar depois.
