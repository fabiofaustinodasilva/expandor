# Pontos

Domínio real: `Property` + `Resident` + `Address`. Não existe API `customers` paralela.

- Lista/busca: `CustomerQueryService` (sellerOwns: criados ou visitados).
- Detalhe: `presentDossier` + tel/wa.
- Criação: `PropertyService` + `ResidentService` (mesma validação de campo).
- Resposta de create devolve marker mínimo para o app inserir sem reload total.
- `company_id` nunca vem do payload; tenancy do token.
