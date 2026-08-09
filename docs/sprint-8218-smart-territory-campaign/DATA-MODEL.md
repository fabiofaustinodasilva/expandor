# DATA-MODEL

```
Company
  └── City (state UF, ibge_code?)
        └── Sector (unique per city+company)
              └── Address.sector_id? 
                    └── Property (ponto)

Campaign
  ├── city_id (required)
  ├── campaign_sectors (0..N)  → empty = ALL sectors of city
  └── campaign_users
```

## Semântica “Todos os setores”

Não criar Sector fake.  
`campaign.sectors` vazio ⇒ território = cidade inteira.
