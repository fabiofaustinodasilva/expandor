# GeoSales Mobile

O aplicativo Flutter oficial vive em um projeto separado:

`C:\Users\Fabio Faustino\Desktop\geosales-mobile`

## API Mobile v1 (este backend)

Base: `/api/mobile/v1`

| Método | Endpoint | Auth |
|--------|----------|------|
| POST | `/login` | público |
| GET | `/me` | Sanctum + `sales_app.access` |
| GET | `/dashboard` | Sanctum |
| GET | `/campaigns` | Sanctum |
| GET | `/campaign/{id}/properties` | Sanctum (só campanhas atribuídas) |
| GET | `/campaign/{id}/markers` | Sanctum |
| POST | `/visits` | Sanctum |
| GET | `/pending-sync` | Sanctum |

Regras: isolamento por `company_id` (tenancy) e atribuição em `campaign_users`.
