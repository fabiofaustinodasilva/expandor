# WEB × MOBILE — Sheet parity (Novo ponto / Confirmar venda)

Referência web: `resources/views/maps/index.blade.php` `#point-modal` + `partials/sale-finalize-fields.blade.php` + `RegisterFirstApproachAction`.

| Elemento | Web | Mobile | Status | Service/Enum compartilhado |
|----------|-----|--------|--------|----------------------------|
| Título | Novo ponto | Novo ponto | OK | — |
| Local | Localização / Posição pronta | Local encontrado / Posição pronta | OK | GPS interno |
| Ajustar posição | Sim | Sim | OK | MapAdapter |
| Nome / responsável | contact_name | contact_name | OK | ResidentService |
| Telefone / WhatsApp | contact_phone | contact_phone | OK | ResidentService |
| Observação curta | notes | notes | OK | Visit notes |
| Situação / interesse | 5 chips VisitStatus | 5 chips VisitStatus | OK | VisitStatus + MapMarkerColor |
| Interessado | status + notes | status + notes | OK | VisitService |
| Não interessado | no_interest | no_interest (“Não interessado”) | OK | VisitStatus |
| Retornar depois | follow_up_at + Agenda | follow_up_at + Agenda | OK | VisitService::scheduleFollowUp |
| Não encontrado | not_home | not_home | OK | VisitStatus |
| Venda realizada | sale-finalize no mesmo sheet | Confirmar venda no mesmo sheet | OK | SaleFieldsPolicyResolver |
| Produtos | sellableOptions / items[] | ProductCatalogService / items[] | OK | ProductCatalogService |
| Total | calculado UI / servidor verdade | UI display / servidor verdade | OK | VisitService sale |
| Comissão | CommissionAwardedPayload | CommissionAwardedPayload | OK | Commissions |
| Campanha | resolveCampaign 0/1/N | campaign_context + resolveCampaign | OK | RegisterFirstApproachAction |
| Cidade | select (auto default) | select oculto se 1 cidade | OK | TerritoryRepository |
| Setor | não no form seller | input texto → sector_id ou neighborhood | OK | resolveSectorFromText |
| Lat/Lng | hidden | hidden | OK | — |
| Status=Novo | não (seller) | removido | OK | PropertyStatus::NEW interno |
| Scroll | header/body/footer | header/body/footer | OK | CSS dvh |
| Salvar | POST /map/first-approach | POST /api/mobile/v1/first-approach | OK | RegisterFirstApproachAction |
| Visita existente | visit-modal | visit-sheet | OK | RegisterVisitAction |

## Setor/Bairro (contrato)

Backend exige `sector_id` opcional (integer) ou nada. Mobile envia `sector_name` (texto).
`MobileSellerOpsService::resolveSectorFromText`:
- match case-insensitive em setores ativos da cidade → `sector_id`;
- senão → `neighborhood` em Address (sem inventar ID).
