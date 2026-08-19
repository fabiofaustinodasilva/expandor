# Planos comerciais (site) vs billing

Nesta sprint o site **não** altera `PlanSeeder`, subscriptions nem checkout.

## O que o visitante vê

| Plano | Equipe | Preço no site | CTA |
|---|---|---|---|
| Start | Até 2 vendedores | R$ 349/mês | Agendar demonstração |
| Pro (Mais escolhido) | Até 5 vendedores | R$ 449/mês | Agendar demonstração |
| Scale | Vendedores ilimitados* | R$ 649/mês | Agendar demonstração |
| Enterprise | Operações maiores / integrações | Sob consulta | Agendar demonstração |

Mensagem: “Todo o poder do Expandor. Escolha pelo tamanho da sua equipe.”
Os três planos pagos de display listam o mesmo conjunto de recursos reais.
Nota: `*Plano Scale sujeito à política de uso justo.` (sem contrato jurídico nesta sprint).

Fonte: `config/marketplace_defaults.php` → `commercial_plans`.

## O que o backend ainda tem (não migrado)

| Slug | Nome | Preço | Limite |
|---|---|---|---|
| `free` | Free | R$ 0 | 3 usuários |
| `professional` | Professional | R$ 199,90 | 25 usuários (featured) |
| `enterprise` | Enterprise | R$ 499,90 | ilimitado |

Checkout `/assinar` e `Plan` no banco **continuam** nesses valores. `/cadastro` e trial técnico permanecem.

## Decisão pendente (sprint de billing)

Alinhar slugs/preços/limites Start/Pro/Scale com o catálogo comercial, desativar display do Free sem apagar dados, e só então religar “Assinar” se o processo deixar de exigir demonstração.
