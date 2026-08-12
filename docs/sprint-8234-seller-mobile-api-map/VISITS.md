# Visitas

`POST /points/{id}/visits` chama `RegisterVisitAction` → `VisitService::register`.

Statuses reais: interested, return_later, no_interest, not_home, wrong_address, installation_requested.

Campos condicionais continuam no backend (`StoreVisitRequest` / SaleFieldsPolicy). O app não é fonte de verdade.

GPS da visita é opcional (`latitude`/`longitude` no payload).
