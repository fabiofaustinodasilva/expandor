# CURRENT-ARCHITECTURE

## Dois níveis desejados

```
ADMIN EXPANDOR                         EMPRESA TENANT
Integrações (catálogo)                 Integrações (conexões)
  - ativar na plataforma                 - configurar se plano permite
  - planos mínimos                       - credenciais próprias
  - capabilities / fallback              - testar / desconectar
  - NÃO ver API key completa da empresa  - seller herda (sem UI técnica)
```

## Fluxo de resolução (mapa premium)

```
Plano permite? → Empresa configurou? → Credencial válida?
        ↓ qualquer NÃO
   provider padrão (Leaflet + OSM/Esri)
```

## O que já existe hoje

| Peça | Estado |
|------|--------|
| `/map` Leaflet | Produção (manager + seller) |
| `ExpandorMapProvider` | Adapter JS; só `leaflet_osm` |
| `operations.integrations` | Placeholder “Em breve” |
| `company_settings.map_provider` | Escrito no provision; **não lido** no runtime |
| WhatsApp credentials | Tenant + `encrypted:array` |
| Mercado Pago | Global `payment_gateway_settings` + `.env` |
| FeatureFlag + PlanCatalog | Padrões distintos (ver PLANS-FEATURES.md) |

## O que NÃO existe

- Tabela `company_integrations`
- Feature `integrations.google_maps`
- Google Maps JS/Tiles SDK
- IntegrationResolver / cache de provider
- Tela Super Admin “Integrações” (só stubs tenant)
