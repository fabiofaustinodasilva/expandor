# DATA-MODEL

```
[GLOBAL]
geo_states (ibge_id, uf, name)
  └── geo_municipalities (ibge_code UNIQUE, name, geo_state_id)

[TENANT]
cities
  ├── company_id
  ├── name, state, ibge_code  (mantidos)
  └── geo_municipality_id? → geo_municipalities
        └── sectors (áreas personalizadas)
              └── campaign_sectors
campaigns.city_id → cities
```

Toda a cidade = pivot `campaign_sectors` **vazio**.
