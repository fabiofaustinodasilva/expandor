# UX flow — CONFIRMAR VENDA (EXP Vendedor)

Ponto → Registrar visita → **Venda realizada** (outcomes coloridos intactos) → ficha no mesmo sheet (header / body rolável / footer fixo).

## Seções (uma tela, steps leves)

1. **Dados do cliente** (azul) — Nome, CPF, nascimento, telefone. Máscaras. RG/e-mail/WhatsApp extra só se a política exigir.
2. **Endereço** (slate) — rua, número, bairro, referência, cidade (API, readonly). Sem lat/lng na UI.
3. **Contratação** (laranja) — chips 5/10/15/20/25/30 + produtos do catálogo.
4. **Revisão** — resumo + footer **Confirmar venda**.

## Depois do 2xx

Sheet **Venda realizada!** com Sale ID, comissão e status do backend.

- Enviar para o escritório (só se `whatsapp_enabled`)
- Copiar mensagem (Clipboard Capacitor → `navigator.clipboard`)
- Ver mensagem (sheet interno, texto selecionável)
- Voltar ao mapa

Detalhe do ponto vendido: bloco **Encaminhamento ao escritório** (reenvio sem redigitar).

Erro de rede: *Não foi possível concluir a venda. Verifique sua conexão e tente novamente.* Marker só atualiza após 2xx. Botão desabilitado até erro.
