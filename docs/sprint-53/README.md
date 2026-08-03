# Sprint 5.3 — Módulo Clientes (CRM Comercial)

## Objetivo
CRM central reutilizando Property + Resident + Visit + Sale + FollowUp + SalesCommission + Product.
Sem tabela de clientes. Sem duplicar regras de negócio.

## Permissões
- `customers.view` — Seller / Manager / Admin / Supervisor
- `customers.manage` — Manager / Admin / Supervisor
- Migration idempotente: `2026_08_03_220001_ensure_customer_permissions`

## Escopo
| Perfil | Lista / ficha |
|--------|----------------|
| Seller | Criou ou visitou |
| Manager / Admin / Supervisor | Empresa |
| Tenant | Isolamento padrão |

## Rotas
- `GET /clientes` → `customers.index`
- `GET /clientes/{property}` → `customers.show`

## Menu
Rail operacional (Seller + Gestão) + “Mais” → Comercial → Clientes

## Ficha
Dados, resumo, timeline, produtos, agenda, comissões (se houver), histórico de visitas.
Ações rápidas reutilizam rotas existentes (`map.index?property=`, `visits.follow-ups.create`, WhatsApp/tel).

## Situação (UX only)
`CommercialTerminology::customerSituation()` — não altera enums.

## Testes
`tests/Feature/Customers/CustomersModuleTest.php`
