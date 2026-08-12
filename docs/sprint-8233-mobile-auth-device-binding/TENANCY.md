# TENANCY

E-mail **é único globalmente** desde a migration `2026_08_05_120001` (`users.email` unique).
O par `(company_id, email)` existia antes; o índice composto foi substituído pelo unique global.

Login mobile resolve o tenant pelo único usuário daquele e-mail (após conferir a senha).
`company_id` opcional permanece para o caso de dados legados/ambiguidades.

## App

1. Busca candidatos pelo e-mail (sem global scope)
2. Filtra por `company_id` se enviado
3. Confere a senha em cada candidato
4. 0 match → `invalid_credentials` (não revela tenant)
5. 1 match → usa essa empresa
6. 2+ matches com a **mesma senha** → `422 tenant_required` + lista `{id,name}` das empresas

Não escolhe o primeiro tenant no escuro.

Web login legado (`first()` no e-mail) **não** foi alterado.

Tenant das APIs autenticadas: `tenancy.initialize` + `tenancy.active` como já existia. Token não substitui company_id do usuário.
