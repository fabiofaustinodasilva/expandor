# TOKEN-LIFECYCLE

## Emissão

- Nome: `seller-app`
- Ability: `seller-app` (não `*`)
- `expires_at`: null (política Sanctum atual)
- Revogação: logout, password reset, ou geração (`session_version`) na request

## Por que token persistente no MVP

`config/sanctum.php` `expiration` = null. O token **não é a sessão**:

- Login novo incrementa `users.session_version`
- Request mobile compara a versão carimbada no PAT
- Token antigo autenticado pelo Sanctum ainda é **rejeitado** pelo middleware

Não há token eterno sem revoke: logout apaga o PAT; reset apaga todos; login novo invalida a geração.

## Rotação

Não há refresh token nesta sprint. Relogin emite PAT novo com a geração atual.

## Por que migration

Abilities não cabem device_id + session_version de forma segura. Tabela extra de “device sessions” duplicaria o PAT. Extensão **aditiva** do model Sanctum (sem alterar colunas do package) é o mínimo correto.

Não editamos o package Sanctum. `Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class)`.
