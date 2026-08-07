# Configurações, Billing UI, Marketplace e Academia

## Configurações

**View:** `operations/settings.blade.php`  
**Rota:** `operations.settings` → `/operacao/configuracoes`

### Achado crítico

A página **existe** e agrega atalhos (venda, campo, integrações,
branding, plano…), mas **não está em `ClientNav::sections()`** nem no
rail. Só aparece via back-links de páginas filhas (**P0** descoberta).

| Achado | Sev |
|--------|-----|
| Ausente do Mais / ClientNav | P0 |
| Cards com emoji (📦💰) | P1 |
| Sobreposição com seção Empresa do Mais | P1 |
| Fora do design system pleno | P2 |

### Oportunidades

| Ideia | Impacto | Estimativa |
|-------|---------|------------|
| Incluir “Configurações” em Empresa (Mais) | Alto | 0,25 d* |
| Lucide + `section-card` | Médio | 0,5 d |
| Fundir visualmente com cards Empresa (evitar dois hubs) | Alto | 1 d |

\*Alterar `ClientNav` é escopo Support/UI — típico de sprint de
implementação UX, não desta auditoria.

---

## Billing / Plano / Assinatura (UI)

**Views:** `billing/plan.blade.php`, `payments/subscription.blade.php`  
**Mais:** “Plano e uso” + “Minha assinatura”

| Achado | Sev |
|--------|-----|
| Duas páginas com “plano atual” sobreposto | P1 |
| Shell `app` (thrash) | P2 |
| Sem `x-client` | P2 |
| Settings também aponta plano | P2 |

**Oportunidade:** uma página “Plano & cobrança” com tabs Uso |
Assinatura | Histórico (UI only se controllers já servem os dados).

**Fora de escopo desta auditoria:** alterar Mercado Pago / webhooks /
regras de cobrança.

---

## Marketplace (público)

**Views:** `marketplace/*`  
Não é módulo operacional do tenant logado (exceto upsell/trial).

| Achado | Sev |
|--------|-----|
| Emoji em listas de plano | P3 |
| Conversão tenant entra via billing — ok | — |

Tratar em sprint de marketing site, não no redesign operacional.

---

## Academia (Training)

| Superfície | Shell | Achado | Sev |
|------------|-------|--------|-----|
| Admin categorias/conteúdos | `app` | Tabelas cruas, sem crud-toolbar | P2 |
| Campo `sales-app/training` | sales-app | OK no bottom nav | — |
| Entrada admin | Só Mais → Sistema | Aceitável se label claro | P2 |

**Oportunidade:** admin listagens no padrão client; progresso do
vendedor no Início do Sales App.

---

## Identidade / Integrações / Perfil

Dispersos sob Empresa e Perfil. Funcionam, mas o **hub Configurações
órfão** impede o mental model “tudo de setup em um lugar”.

Prioridade: **descoberta do hub** > polish visual.
