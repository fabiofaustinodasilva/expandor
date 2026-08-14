# Fluxo de venda (Web)

1. Vendedor escolhe **Venda realizada**.
2. Sheet **CONFIRMAR VENDA**: cliente (azul) → endereço (slate) → contratação/vencimento/produtos (laranja) → revisão (verde).
3. Rodapé: Cancelar / Confirmar venda.
4. Backend valida → transaction: residente, endereço, Sale, SaleItems, comissão, visita/imóvel.
5. 2xx com `office_handoff` **depois** do commit.
6. Modal de sucesso: enviar / copiar / ver / voltar ao mapa.

Prefill: Resident + Address do ponto. Campanha e vendedor autenticado. Preços só do catálogo.
