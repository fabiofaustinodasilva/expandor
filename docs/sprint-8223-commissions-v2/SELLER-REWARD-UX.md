# Seller reward UX

## Contrato API

Após Contratar / FirstApproach com `installation_requested`, JSON inclui:

```json
"commission_awarded": {
  "amount": 18.50,
  "currency": "BRL",
  "sale_id": 123,
  "visit_id": 456,
  "play_reward": true
}
```

`play_reward = amount > 0`.

## Feedback visual

- Texto curto: **VENDA FECHADA!** + “Você ganhou R$ X de comissão”
- Duração ~1–2s, não bloqueia mapa
- Comissão 0: manter toast de venda atual, sem celebração de dinheiro

## Som

- Arquivo local Expandor: `/sounds/commission-coins.mp3` (fallback Web Audio se falhar)
- Volume discreto; uma vez por operação confirmada
- Falha de áudio **nunca** afeta a venda
- Autoplay: só após gesto do usuário (submit do formulário)

## Idempotência do feedback

Chave: `sessionStorage['expandor.commission_rewarded.' + visit_id]`  
Setada **somente** após sucesso desta resposta — não “última comissão existe”.

Reload da página: não toca de novo.

## Capacitor (futuro)

Evento lógico: `sale.commission_awarded`  
Web: sound + animation. Native: sound + haptic leve. Não implementar agora.
