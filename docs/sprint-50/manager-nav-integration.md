# Integração Sprint 5.0 no Manager operacional

## Análise

| Shell | Arquivo | Papel |
|---|---|---|
| **Novo Manager** | `layouts/operational.blade.php` (op-rail) | Uso diário do gestor |
| **Antigo** | `layouts/app.blade.php` (sidebar) | CRM / telas técnicas |
| **Hubs** | `operations/settings`, `operations/more` | Atalhos |

Sprint 5.0 já usava layout operacional e rotas `/comissoes` + `/operacao/configuracoes/produtos`.
**Gap:** Produtos/Estoque só apareciam em Config; “Mais” e sidebar antiga não listavam o módulo.

## Integração (sem duplicar telas)

- Rail Manager: **Comissões** + **Produtos** (`commissions.manage`)
- Estoque permanece **dentro de Produtos** (arquitetura atual)
- Mais + Config: atalhos para as mesmas rotas
- Sidebar `app`: mesmos links (quando o gestor cai no CRM antigo)
- Controllers / Services / Policies da Sprint 5.0 **reutilizados**

## Permissões

- `commissions.manage` — gestão completa (Admin/Manager/Supervisor)
- `commissions.view_self` — Seller (Minha comissão)
- Exposto também em `CommercialProfileCatalog` (grupo COMERCIAL)
