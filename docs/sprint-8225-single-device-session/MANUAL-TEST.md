# MANUAL-TEST

## Seller (Chrome A + anônimo B)

1. Login Seller em A → abrir `/map`
2. Login mesmo Seller em B → `/map` OK em B
3. Em A: F5 ou ação no mapa
4. A deve ir ao login com:
   `Sua conta foi acessada em outro dispositivo. Por segurança, esta sessão foi encerrada.`
5. B continua OK

## Administrator A+B

Ambas as sessões permanecem válidas.

## Password reset

1. Seller logado em A
2. Reset senha
3. A perde sessão na próxima request
4. Login com nova senha funciona
