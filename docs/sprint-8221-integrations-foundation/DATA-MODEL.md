# DATA-MODEL

## Decisão

Não havia tabela tenant genérica reutilizável para secrets de integrações (WhatsApp é dedicado; `company_settings` é plaintext; MP é global).

Criada: **`company_integrations`**

## Schema

| Coluna | Tipo | Notas |
|--------|------|-------|
| id | bigint | PK |
| company_id | FK | TenantScope via BelongsToTenant |
| provider | string | ex. `google_maps` |
| category | string | ex. `maps` |
| enabled | bool | |
| status | string | `disconnected` \| `connected` \| `error` |
| configuration | json | público / não-secret |
| credentials | text encrypted:array | ex. `{browser_api_key}` |
| last_tested_at | timestamp | |
| last_error | text | sanitizado |
| created_by / updated_by | FK users nullable | |
| timestamps | | |
| UNIQUE | (company_id, provider) | |

Migration: `2026_08_09_100001_create_company_integrations_table.php`

Model: `App\Domains\Integrations\Models\CompanyIntegration`
