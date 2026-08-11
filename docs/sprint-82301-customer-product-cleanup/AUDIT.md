# AUDIT

## Cliente — o que é?

Não existe tabela `customers` no CRM.

| Conceito UX | Model |
|-------------|-------|
| Cliente (lista `/clientes`) | `Property` (ponto no mapa) + `Resident` primário (nome/telefone) |
| Ponto / imóvel | O mesmo `Property` |
| Payments `Customer` | Gateway — **outra coisa** |

## Respostas Fase A

1. **Cliente pode ser excluído fisicamente sem perder histórico?**  
   **Não.** Hard-delete de `properties` dá `cascadeOnDelete` em residents, visits, sales, commissions, follow-ups, messages.

2. **Produto pode ser excluído fisicamente sem quebrar vendas antigas?**  
   **Não, se já foi usado.** Snapshots (8.2.23) guardam nome/valor, mas `product_id` FKs (`nullOnDelete`) e joins vivos ainda existem. `stock_movements` **cascade**.

3. **SoftDeletes já existe?**  
   Property: **sim**. Product, Visit, Sale, Resident: **não**.

4. **Snapshots suficientes?**  
   Para recálculo de comissão: sim. Para apagar Product usado: **não**.

5. **O que bloqueia exclusão?**  
   Cliente: qualquer `Visit`. Produto: comissão **ou** sale_item **ou** sale **ou** visit com `product_id`.

6. **O que pode ser excluído se nunca usado?**  
   Cliente sem visitas → soft-delete Property. Produto sem vínculos → hard-delete.

7. **Quando desativar?**  
   Produto com histórico (sai do catálogo futuro).

8. **Quando excluir?**  
   Só registro sem uso comercial.

## FKs perigosos (não alterados)

`visits.property_id`, `residents.property_id`, `sales.visit_id`, `sales_commissions.visit_id` → **cascade**.  
Nenhum cascade destrutivo foi adicionado.
