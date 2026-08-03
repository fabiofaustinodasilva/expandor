# Sprint 5.2.2 — Busca Inteligente no Mapa do Seller

## Objetivo
Mesma barra de busca do Manager no mapa do Seller, reutilizando `GET /api/v1/maps/markers` com parâmetro `q`. Sem módulo novo e sem migrations.

## Escopo
| Perfil | Escopo da busca (`q`) |
|--------|------------------------|
| Seller | Imóveis que criou **ou** visitou |
| Manager / Admin / Supervisor | Empresa inteira |
| Tenant | Isolamento inalterado |

## Campos pesquisáveis
Nome, telefone, WhatsApp, CPF, rua, número, bairro, cidade, produto (visita/`sale_items`).

## UX
- Seller: barra de busca visível
- 1 resultado → flyTo + zoom + drawer + destaque do pin
- N resultados → lista + fitBounds; seleção foca o ponto
- Drawer: cliente, telefone, WhatsApp, última visita (data/resultado), próxima ação, retorno, produto vendido

## Arquivos
- `MapFiltersDTO` (`q`, `search_owner_user_id`)
- `MapRepository::applyTextSearch`
- `MapQueryService::constrainForUser` (escopo por perfil na busca)
- `MapPointController::show` (payload do drawer)
- `maps/index.blade.php` + `operational-map.js` v41
- Testes: `tests/Feature/Maps/MapSmartSearchTest.php`
