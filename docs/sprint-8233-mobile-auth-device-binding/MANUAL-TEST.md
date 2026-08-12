# MANUAL-TEST

Pré: `CAP_API_URL` apontando para a API; shell buildado; dois “dispositivos” (dois browsers ou app + web).

## A — App / App

1. Login Seller no App A
2. Confirmar `/me`
3. Login mesmo Seller no App B (outro `device_id`)
4. B funciona
5. A faz request → `session_replaced` → volta ao login

## B — Web / App

1. Login Seller na web
2. Login mesmo Seller no app
3. Atualizar a web → cai (mensagem 8.2.25)

## C — App / Web

1. Login Seller no app
2. Login mesma conta na web
3. App faz request → `session_replaced`

## D — outro Seller

Login de outro vendedor não derruba o primeiro.

## E — Esqueci senha

No shell, o link abre `/esqueci-minha-senha` no browser. Completar reset; token mobile anterior falha.
