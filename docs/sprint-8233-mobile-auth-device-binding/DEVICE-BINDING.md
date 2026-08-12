# DEVICE-BINDING

`device_id` identifica a **instalação** do app (UUID v4), não IMEI/serial/MAC/fingerprint.

## Persistência no token

Colunas aditivas em `personal_access_tokens`:

- `device_id`
- `device_name` (Android / iPhone / Web)
- `platform`
- `app_version`
- `session_version` (geração no momento da emissão)

Sanctum já tem `last_used_at` — não duplicar.

## Validação (toda request device-bound)

1. Token autenticado via Sanctum
2. Se `device_id` no token:
   - `X-Device-Id` obrigatório
   - header deve ser idêntico ao bound
   - `token.session_version === users.session_version`
3. Falha → 401 (`unauthenticated` se header ausente; `session_replaced` se device/versão divergem)

Tokens Sanctum **sem** `device_id` (legado / `Sanctum::actingAs` / `/api/v1/auth`) não entram neste binding.

## Edge cases

| Caso | Resultado |
|---|---|
| App reinstalado | Novo UUID → novo login vence o anterior |
| Token copiado + device header diferente | 401 `session_replaced` |
| device_id ausente no login | 422 `validation_error` |
| device header ausente na request | 401 `unauthenticated` |
| device_id sozinho | não autentica |

O cliente **não** pode forjar `session_version`. Só o servidor incrementa.
