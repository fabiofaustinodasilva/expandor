# Planos comerciais (site) vs billing

O catálogo oficial é o mesmo no site e no SaaS.

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

Fonte de display: `config/marketplace_defaults.php` → `commercial_plans`.
Fonte SaaS: `PlanSeeder` + `CommercialPlanCatalog` (`plans` table).

## Catálogo SaaS

| Slug | Nome | Preço | Vendedores | Checkout público |
|---|---|---|---|---|
| `start` | Start | R$ 349,00 | 2 | sim (máquina interna; CTA pública é demo) |
| `pro` | Pro | R$ 449,00 | 5 | sim (máquina interna; CTA pública é demo) |
| `scale` | Scale | R$ 649,00 | ilimitado (`max_sellers` null) | sim (máquina interna; CTA pública é demo) |
| `enterprise` | Enterprise | sob consulta (`price` 0) | ilimitado | não |
| `free` | Free | R$ 0 | legado interno | não |
| `professional` | Professional | R$ 199,90 | legado (`max_users` 25) | não |
| `enterprise-legacy` | Enterprise (legado) | R$ 499,90 | legado | não |

`/assinar` e `/cadastro` públicos redirecionam para `#demo`.
Checkout HTTP (`/checkout?plan_id=`) só aceita `start`, `pro` e `scale`. Pagamento de um novo gateway **não** entra nesta sprint.

O limite comercial conta apenas vendedores (`role=seller`) ativos. Admin e Manager não ocupam vaga.
