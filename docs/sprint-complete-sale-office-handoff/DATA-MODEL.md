# Modelo de dados

## Novas colunas (aditivas)
- `residents.birth_date` DATE NULL
- `addresses.reference` VARCHAR(255) NULL
- `sales.due_day` UNSIGNED TINYINT NULL (5, 10, 15, 20, 25, 30)

## Settings (sem migration)
- `office_sales_whatsapp`
- `office_sales_whatsapp_enabled` (`1`/`0`)

## Contrato de handoff
`App\Domains\Sales\Handoff\SaleHandoffData` — mesma estrutura para WhatsApp hoje e ERP/PDF no futuro.
