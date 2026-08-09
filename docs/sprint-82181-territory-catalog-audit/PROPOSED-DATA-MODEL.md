# PROPOSED-DATA-MODEL (não implementado)

```
[GLOBAL]
geo_states
  └── geo_municipalities (ibge_code UNIQUE)
        └── geo_neighborhoods? (futuro, opcional)

[TENANT]
cities  ← materializa município (company_id + geo_municipality_id?)
  └── sectors ← personalizado OU materializado de bairro
        └── campaign_sectors (INALTERADO)
campaigns.city_id (INALTERADO)
addresses.city_id / sector_id (INALTERADO)
```

Regra: catálogo informa; tenant opera.
