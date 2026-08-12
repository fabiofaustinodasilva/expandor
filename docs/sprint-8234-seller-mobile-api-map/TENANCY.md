# Tenancy

Todo endpoint autenticado: `tenancy.initialize` + `tenancy.active` + policies.

ID de outro tenant → 404 (`not_found`) no detalhe de ponto; 403 quando a policy negar.

Nunca confiar em `company_id` do body. Tenant vem do usuário autenticado.
