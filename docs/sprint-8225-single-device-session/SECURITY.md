# SECURITY

- Sem senha/token/cookie completo em audit
- Audit: `auth.session_replaced` (login Seller), `auth.session_invalidated` (kick / password reset)
- Metadados: `session_version` (inteiro), `reason` — sem session ID cru
- Validação server-side (middleware); não depende de localStorage/JS
- Remember-me: token rotacionado no login Seller
- Tenancy: opera por `user_id`; sem query só por `company_id`
