# SYNC

## Fila (proposta)

```
operação local → queue (pending)
→ online → POST API com Idempotency-Key
→ 2xx → confirmed + reconcilia ID local→server
→ 409/422 de negócio → needs_attention
→ 5xx/timeout → retry backoff
```

IDs temporários: `tmp_{uuid}` no cliente. Backend devolve id real. UI troca sem o usuário ver.

## Conflitos (MVP)

Preferir **append-only**: nova visita / novo ponto / novo follow-up.  
Não editar visita histórica offline.

| Tipo | Estratégia |
|------|------------|
| Criar visita/venda | server confirma; duplicata evitada por idempotency |
| Criar ponto | server wins se pin já existe no mesmo lat/lng (definir raio depois) |
| Completar follow-up | server wins se já completed |
| Editar cliente | **fora do MVP offline** |

## UX

Automático ao `online`.  
“Sincronizando…” / “Tudo sincronizado” / “1 ação precisa de atenção”.  
Sem JSON cru.

## Comissão

Estimativa local opcional. Oficial + reward + som **só após 2xx do servidor**. Pending: “Venda enviada — confirmando comissão”.
