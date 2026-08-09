# TENANCY

- City/Sector/Campaign/Address/Property: `BelongsToTenant` + TenantScope
- `sectors-for-city`: `City::findOrFail` (404 cross-tenant)
- Validation: `exists` com `company_id` do tenant em `sector_ids.*` / `city_id`
- Sync: `Sector::where('city_id', campaign.city_id)` (TenantScope) — rejeita setor de outra cidade/empresa
- Mapa: `Campaign::find` tenant-scoped; campanha inexistente ⇒ zero markers
- Seller: FieldOps + território da campanha; frontend não contorna TenantScope
