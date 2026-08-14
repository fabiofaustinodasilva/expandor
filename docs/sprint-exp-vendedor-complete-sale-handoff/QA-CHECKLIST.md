# QA checklist — Xiaomi / Android Studio Run

Não gerar APK de store nesta etapa. Usar Run no Android Studio após `npm run build` + `npx cap sync android`.

## A — Venda nova

- [ ] Novo ponto → Venda realizada
- [ ] Preencher cliente (máscaras), endereço, vencimento chip, produto
- [ ] Revisão correta
- [ ] Confirmar → sucesso, Sale ID, comissão
- [ ] Copiar mensagem / WhatsApp / voltar ao mapa

## B — Reenvio

- [ ] Fechar sucesso → mesmo ponto → Encaminhamento
- [ ] Ver / Copiar / Reenviar WhatsApp sem redigitar

## C–F

- [ ] Zoom/pan: marker permanece
- [ ] Toque no marker abre detalhes
- [ ] Ajustar posição funciona
- [ ] Retornar depois continua gerando agenda

## Erros

- [ ] 422 mostra texto humano (CPF, vencimento, nascimento)
- [ ] Sem rede: mensagem de conexão; pin não vira vendido
- [ ] Duplo toque não cria duas vendas
- [ ] WhatsApp da empresa desligado: some Enviar; Copiar/Ver ficam
