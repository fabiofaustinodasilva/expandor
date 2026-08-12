# Contrato API mobile Seller

Envelope de sucesso:

```json
{ "success": true, "message": "...", "data": {}, "meta": {} }
```

`meta` só aparece em listas paginadas.

Erro:

```json
{ "success": false, "message": "Mensagem humana", "code": "validation_error", "errors": {} }
```

## GET `/bootstrap`

Auth: Bearer + `X-Device-Id`.  
Response `data`: user, company, role, permissions, feature_flags, map (provider/fallback/google_visual/attribution; browserKey só se entitled), timezone, capabilities, session.  
Nunca: APP_KEY, SMTP, server key, secrets.

## GET `/markers`

Query: `min_latitude`, `max_latitude`, `min_longitude`, `max_longitude`, `q`, `campaign_id`, filtros já existentes em `MapMarkersRequest`.  
Sem bbox + sem busca: escopo FieldOps do seller (próprios). Não carregar cidade inteira se o app enviar viewport.

## GET `/points`

Query: `q`, `status`, `per_page` (1–50). Debounce frontend 400 ms.

## POST `/points`

Body: city_id, sector_id?, street, number?, latitude, longitude, status, contact_name?, contact_phone?, notes?

## GET `/points/{id}`

404 `not_found` se outro tenant ou seller sem posse.

## POST `/points/{id}/visits` e `/sales`

Reusa `StoreVisitRequest`. `campaign_id` obrigatório. Venda = `status=installation_requested` + items/qty + customer_name/phone. Comissão nunca vem do app.

## GET `/agenda?scope=today|upcoming|overdue|all`

Wall-clock `America/Sao_Paulo` via `AppTime::today()` / `whereDate(scheduled_at)`.

## POST `/follow-ups/{id}/complete`

Reusa regras de `CompleteFollowUpRequest`.

## GET `/products`

Somente `isSellable()`.

## GET `/commissions`

Somente do seller autenticado. Summary existente.

## GET `/results`

`visits_today`, `pending_follow_ups`, `active_campaigns` + `commissions` summary. Sem KPI novo.

Códigos: `validation_error`, `not_found`, `forbidden`, `unauthenticated`, `session_replaced`, `out_of_stock` (domínio), `sale_error` (quando o serviço lançar).
