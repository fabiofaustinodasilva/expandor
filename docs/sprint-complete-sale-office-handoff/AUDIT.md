# Auditoria de campos

| Campo | Já existe? | Model/tabela | Migration? | Reaproveitar? | Obrigatório |
|---|---|---|---|---|---|
| Nome | Sim | residents.name | Não | Sim | Sim (policy name) |
| CPF | Sim | residents.document | Não | Sim | Policy document |
| Nascimento | **Não** | residents.birth_date | **Sim (aditiva)** | Novo | Opcional no backend; UI captura |
| Telefone | Sim | residents.phone / whatsapp | Não | Sim | Policy phone |
| Rua | Sim | addresses.street | Não | Sim | Opcional se já no ponto |
| Número | Sim | addresses.number | Não | Sim | Opcional |
| Bairro | Sim | addresses.neighborhood | Não | Sim | Opcional |
| Referência | **Não** | addresses.reference | **Sim (aditiva)** | Novo | Opcional (omite se vazio) |
| Cidade | Sim | addresses.city_id → cities.name | Não | Sim | Opcional (match por nome) |
| Vencimento | **Não** | sales.due_day | **Sim (aditiva)** | Novo | UI exige; backend valida 5/10/15/20/25/30 |
| Produtos | Sim | sale_items + ProductCatalogService | Não | Sim | Policy product |
| Comissão | Sim | sales_commissions | Não | Sim | Gerada no backend |
| Vendedor | Sim | visits.user_id | Não | Sim | Autenticado |
| Sale ID | Sim | sales.id | Não | Sim | — |
| Lat/Lng | Sim | properties | Não | Sim | Não exibidos como campo técnico |
| WhatsApp escritório | Parcial companies.whatsapp | company_settings keys | Não | Sim + toggle | Config da empresa |

SoftDeletes em campanha: N/A. Sale não usa SoftDeletes.

Foreign keys de venda inalteradas. Sem cascade destrutivo extra.
