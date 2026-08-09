# Seller reward UX

## Contrato API

Após Contratar / FirstApproach com `installation_requested`, JSON inclui:

```json
"commission_awarded": {
  "commission_id": 99,
  "amount": 18.50,
  "currency": "BRL",
  "sale_id": 123,
  "visit_id": 456,
  "awarded": true,
  "play_reward": true
}
```

`awarded` / `play_reward` = amount > 0 **e** comissão persistida.

## Overlay

- Markup `#commission-reward` no mapa
- Texto: Venda fechada! / Você ganhou / R$ X / de comissão
- ~2.6s, auto-dismiss, não bloqueia mapa
- Formatação pt-BR via `toLocaleString`

## Som

- `/sounds/commission-coins.wav` local
- preload + unlock no primeiro gesto
- Falha de áudio nunca afeta a venda

## Idempotência

- Chave: `sessionStorage['expandor.commission_reward_shown_{commission_id}']`
- Flash Laravel one-time no próximo GET do mapa (seller)
