# Correções realizadas — Sprint 5.5.5

## Terminologia comercial

- Menu `layouts.app`: **Imóveis** → **Clientes / Pontos**; **Implantação** → **Setup**
- Menu Mais (`MoreController`): label alinhada
- Setup Wizard / dashboard / configurações: “implantação” → **setup**
- Visitas, comissões, sales-app, properties/residents: labels **Cliente / Ponto**
- Status UI via `CommercialTerminology` (Visit/Property options e `status_label` no mapa)
- Fallback CRM: `Residência #` → `Cliente #`
- PWA `manifest.webmanifest`: GeoSales → **Expandor Campo**

## Performance

- Eager load `role.permissions` + `permissionOverrides` em:
  - `MapController`
  - `DashboardController`
  - `MoreController`

## Limpeza

- Removido `PwaBootstrap` não utilizado
- Removida pasta vazia `resources/views/acquisition/signup`
- `php artisan view:clear` (cache Blade com placeholder antigo)

## Controllers / JSON

- `VisitController`, `PropertyController`, `SalesAppCampaignController` usam opções comerciais
- `MapPointController` / `MapFirstApproachController`: `status_label` comercial
