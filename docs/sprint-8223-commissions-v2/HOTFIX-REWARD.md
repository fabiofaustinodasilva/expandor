# Hotfix — Seller commission reward (8223)

## Causas (produção)

1. **Notificação fraca / fácil de não ver**  
   A celebração usava só `#op-toast` (toast genérico no rodapé). No mapa seller mobile o toast compete com a chrome inferior e, no fluxo de visita, `post-visit-modal` pode cobrir. Não havia overlay próprio.

2. **Cache bust ausente**  
   `operational-map.js` ganhou `celebrateCommissionAward` sem subir a query `?v=` (ficou em `v=54` da 8222). Browsers/CDN podiam servir JS antigo **sem** reward, enquanto o backend já calculava comissão corretamente.

3. **Áudio não era “moedas”**  
   `/sounds/commission-coins.wav` era tom sintético (senoides curtas), não jingle de moedas. Fallback Web Audio era beep genérico.

4. **Payload incompleto**  
   Faltavam `commission_id` e `awarded` explícito (só `play_reward`). Sem chave estável de idempotência por comissão.

JSON AJAX do Contratar **não** era perdido por redirect HTTP no caminho principal; ainda assim flash one-time foi adicionado para reload/navegação subsequente.

## Correções

| Item | Solução |
|------|---------|
| Backend | `commission_awarded`: `commission_id`, `sale_id`, `visit_id`, `amount`, `currency`, `awarded`, `play_reward` |
| One-time | `Session::flash` + `pullFlash` no GET do mapa (seller); `sessionStorage` por `commission_id` |
| UI | Overlay `#commission-reward` (bottom-sheet mobile / center desktop), ~2.6s, não bloqueia mapa |
| Áudio | WAV local regenerado (~1.3s, cliques metálicos); preload + unlock no gesto; sem beep como substituto |
| Cache | `operational-map.js?v=55` |

## Validação manual

Ver [UX-CHECKLIST.md](./UX-CHECKLIST.md).
