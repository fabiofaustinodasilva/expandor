# Security

- Seller **não** define comissão no request de venda.
- Configuração: Area da Empresa → Produtos (policies de Product / permissões de catálogo).
- Todo registro carrega `company_id`; queries tenant-scoped.
- Cross-tenant: policies existentes em Product, Visit, SalesCommission.
- Audit: `sales_commission.created` via SecurityService.
- Entitlement de módulo Comissão: não misturar nesta sprint sem necessidade — form de produto continua no fluxo comercial existente.
