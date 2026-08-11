# TABLES

## Desktop

Header, row, hover (borda `--border`), alinhamento à esquerda, status em badge, ações à direita.

Moeda: `.table-num` (`text-align: right` + `tabular-nums`) em Preço, Valor da venda, Comissão.

Datas: `AppTime::formatInstant` — **sem reimplementar timezone**.

## Mobile (prioridade 390×844)

| Tela | Estratégia |
|------|------------|
| Produtos | **B** cards via `.client-data-table--responsive` + `data-label` |
| Comissões | **B** mesma primitiva |
| Equipe | já era cards, não tabela |
| Super Admin / Planos | **A** scroll horizontal controlado (densidade) |
| Clientes / Campanhas | mix: cards + scroll onde já existia |

Ações essenciais (Editar, Aprovar, Marcar pago) permanecem visíveis.
