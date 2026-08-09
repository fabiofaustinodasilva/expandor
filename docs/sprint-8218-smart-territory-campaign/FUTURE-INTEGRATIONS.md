# FUTURE-INTEGRATIONS

Não implementar providers nesta sprint.

Futuro — Admin Plataforma → Integrações → Território:

```
TerritoryImportProvider (interface)
  ├── IbgeProvider
  ├── ViaCepProvider / BrasilApiProvider
  └── ManualCompanyProvider (já existe via CRUD setores)
```

Domínio continua com `City`/`Sector` da empresa.  
`ibge_code` já existe para match futuro.  
GPS → geocode → city/sector fica para Central de Integrações (não nesta sprint).
