# Mobile readiness (não implementado)

O APK **não** recebeu este fluxo nesta sprint.

Quando o EXP Vendedor sincronizar uma `Sale`:
1. Chamar o mesmo `SaleHandoffFormatter` (ou endpoint `sales.handoff.show`).
2. Copiar localmente o `message`.
3. Abrir WhatsApp depois, mesmo se a rede falhar no instante da venda.

Não iniciar SQLite/offline/sync agora.
