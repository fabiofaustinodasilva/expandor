# Paridade Web → EXP Vendedor

A Web permanece a fonte de verdade. O app não monta mensagem, não calcula comissão e não hardcodar produtos/WhatsApp.

| Capacidade | Web | Mobile |
|---|---|---|
| Campos cliente | nome, CPF, nascimento, telefone | iguais (`customer_*`) |
| Endereço instalação | `install_*` | iguais; cidade do território/Property |
| Vencimento | chips 5–30, obrigatório na ficha | chips; obrigatório se `complete_sale` |
| Produtos | catálogo empresa, multi-item | `ProductCatalogService` via `/products` |
| Campanha | `activeCampaignsFor()` ACTIVE | mesmo método no bootstrap |
| Sale / comissão | `VisitService` | mesmos actions |
| Handoff | `SaleHandoffFormatter` | GET `/api/mobile/v1/sales/{id}/handoff` |
| WhatsApp escritório | settings da empresa | `whatsapp_url` no payload |
| Marker | MapMarkerColor, lat/lng do imóvel | update após 2xx; GPS da visita não move o pin |
| Legado APK antigo | n/a | venda sem `complete_sale` ainda aceita due_day null |
