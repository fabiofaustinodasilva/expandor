# Handoff

- Formatter: `SaleHandoffFormatter`
- DTO: `SaleHandoffData`
- Service: `SaleHandoffService`
- Rotas: `GET /vendas/{sale}/handoff`, `POST .../copiar`, `POST .../abrir`
- Regenerável a qualquer momento a partir dos dados persistidos.
- Vendedor e comissão **não** vêm do frontend.
- Linhas opcionais vazias são omitidas (nunca `null`).
- Localização: `https://maps.google.com/?q={property.lat},{property.lng}`
- Auditoria: `sale.office_handoff_opened` / `sale.office_handoff_copied` (sem CPF completo / sem texto da mensagem).
