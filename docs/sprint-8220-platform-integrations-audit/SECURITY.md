# SECURITY

## Princípios

1. Distinguir **browser key** (Maps JS, referrer-restricted) de **server secret** (Geocoding).  
2. Browser key **aparece no cliente** por natureza — mitigar com referrer, não fingir que é secret.  
3. Server secrets: encrypted at rest (`encrypted` cast / Crypt), nunca no HTML/logs.  
4. Após salvar: UI mostra máscara `••••••••abcd` (últimos 4).  
5. Nunca logar key completa.  
6. Audit log: quem alterou/desconectou/testou (`company_id`, user_id, integration).  
7. Policies: só `integrations.manage` (ou equivalente) na empresa; Admin plataforma **não** lê key completa.  
8. Rotação: substituir key = update encrypted + invalidate cache.  
9. Remoção: wipe credentials + status disconnected.

## Padrão já usado no projeto

- `WhatsAppConnection.credentials` → `encrypted:array`  
- `PaymentGatewaySetting.access_token` → `encrypted`

Reusar o mesmo padrão Laravel casts.

## Cross-tenant

TenantScope em `company_integrations`; policies `company_id === user.company_id`; testes explícitos.

## Test connection

Endpoint autenticado tenant que:

- Não devolve a key  
- Retorna ok/erro tipado (invalid_key, api_disabled, billing, referrer)
