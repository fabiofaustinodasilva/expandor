# OFFLINE

## Hoje

Fila `expandor.field.offline.queue.v1` (localStorage): `point.create|update`, `visit.create`, `first_approach`. Flush com CSRF same-origin. Sem tiles. Sem API sync real (`GET /api/mobile/v1/pending-sync` é stub).

## Escopo MVP (desenho — não implementar agora)

Seller **consegue**:

- Abrir shell + ver “Offline”
- Ver cache recente (pontos/agenda do último sync) — depois do SQLite
- Enfileirar: criar ponto, visita, venda, retorno
- Sync ao voltar rede
- Entender pendências (não modal bloqueante)

Seller **não** consegue no MVP:

- Mapa com tiles sem rede
- Recalcular comissão oficial offline
- Editar histórico antigo offline

## Por que não implementar sync nesta sprint

Fila atual não tem idempotency estável, não cobre venda/comissão, depende de cookie, localStorage estoura. Implementar agora = risco de venda duplicada.
