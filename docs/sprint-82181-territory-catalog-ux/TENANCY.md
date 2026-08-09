# TENANCY

- `geo_*` = global (sem company_id)
- Materialização sempre com `TenantContext` → City/Sector do tenant atual
- Empresa A e B podem ter City distintas para o mesmo `geo_municipality_id`
- Listagem de áreas usa City materializada do tenant (nunca setores de outro company)
