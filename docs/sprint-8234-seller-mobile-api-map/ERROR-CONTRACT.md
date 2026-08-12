# Erros

| code | HTTP | Quando |
|---|---|---|
| validation_error | 422 | FormRequest / validate |
| not_found | 404 | ponto/retorno inexistente ou outro tenant |
| forbidden | 403 | role/permission/policy |
| unauthenticated | 401 | sem token ou sem `X-Device-Id` |
| session_replaced | 401 | outro device/login venceu |
| network_offline | client | sem rede; mutação não finge sucesso |
| permission_denied / unavailable / timeout / position_error | GPS | LocationService |
| out_of_stock | domínio | `StockService` |

Mensagens humanas. Sem stack/exception no JSON.
