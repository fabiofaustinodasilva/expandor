# AUDIT — Sprint 8.2.11

## Reutilizado

| Área | Fonte |
|------|--------|
| GPS | `locateMyPosition` / `getGps` / `centerMapOnCoords` |
| Cores | `MapMarkerColor::forStatus` / `markForStatus` |
| Comissão | `VisitHistoryPresenter` + `visit.property.residents` |
| Produtos | `Product`, `ProductCatalogService`, `ProductPolicy`, CRUD empresa |
| Imagem | `MediaUploadService` + `MediaPurpose::ProductImage` |
| Vídeo | URL externa (padrão marketplace) — upload binário **não** existe |

## Criado / mínimo

| Item | Decisão |
|------|---------|
| Migration `products` | `category`, `benefits` (json), `sort_order`, `video_url` |
| Sales App | rotas `sales-app.products.*` + views lista/apresentação |
| GPS UI | removidos Meu Local duplicados; lock `locateInFlight` |

## Não criado

- Domínio paralelo de catálogo  
- Model Eloquent `Media` / `ProductVideo`  
- Upload de arquivo de vídeo via MediaUploadService  
- Novas permissões admin para vendedor  
- Alteração de regras/cálculo de comissão  
- Alteração de status comerciais / tenancy / auth / billing  
