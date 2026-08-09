# SECURITY

## Browser key vs server secret

Nesta fase só persistimos **API Key Web** (`browser_api_key`) destinada ao Maps JavaScript futuro.

| Tipo | Onde | Tratamento |
|------|------|------------|
| Browser key | Encrypted at rest; máscara na UI; **não** injetada no `/map` nesta sprint | Restrição HTTP referrer no GCP da empresa |
| Server key | Não coletada ainda | Futuro Geocoding/Routes |

## Controles

- Cast `encrypted:array`
- `$hidden = ['credentials']`
- UI mostra só máscara `••••ABCD`
- Audit log sem key completa (apenas `masked_key` / códigos de teste)
- Policies + `integrations.manage`
- Cross-tenant via TenantScope
- Super Admin vê agregados, nunca a key
- Logs de teste não registram a key

## Teste de conexão

Probe Geocoding server-side. Restrição IP/referrer em chave Web é tratada como **válida para fase browser** (`ok_browser_restriction`).
