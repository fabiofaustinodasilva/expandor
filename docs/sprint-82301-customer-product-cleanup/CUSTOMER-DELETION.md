# CUSTOMER-DELETION

## Domínio

Cliente CRM **é** o `Property`. Excluir cliente sem histórico = soft-delete do ponto. Não há pessoa separada para apagar sem o ponto.

Não usamos `forceDelete`. Residentes e endereço **permanecem** no banco.

## Quem pode

`customers.manage` + mesma empresa + **não** Seller.

## Fluxo

1. Sem visitas → `CustomerDeletionService::deleteOrFail` → SoftDeletes + audit `customer.deleted`.
2. Com visita/venda/follow-up/comissão (via visita) → bloqueio com mensagem amigável. Sem botão Excluir efetivo.

Confirmação: “Esta ação só é permitida para clientes sem histórico.”

Não criamos status Arquivado novo — PropertyStatus comercial já existe; histórico permanece visível na lista.
