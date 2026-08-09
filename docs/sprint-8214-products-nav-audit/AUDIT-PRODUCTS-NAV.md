# AUDIT — Produtos na Área da Empresa (Sprint 8.2.14)

Data: 2026-08-08  
Branch auditada: `feature/sprint-8213-sales-presentation-contract` (`a78f779`)  
Código funcional **não** alterado nesta sprint.

---

## 1. Onde está atualmente o botão Produtos?

| Superfície | Existe? | Destino |
|------------|---------|---------|
| **Rail primário (admin)** `ClientNav::adminRail()` | **Não** | — |
| Página **Equipe** — aba `#team-shortcut-products` | Sim | `commissions.products.index` |
| Página **Financeiro** — `#finance-shortcut-products` | Sim | `commissions.products.index` |
| **Configurações** — card Produtos | Sim | `commissions.products.index` |
| **Mais** (`MoreController`) — Comercial → Produtos | Sim | `commissions.products.index` |
| Nav secundária **Comercial** (`rawSections`) | Sim | `commissions.products.index` |
| Nav secundária **Empresa** (`rawSections`) | Sim (duplicado) | `commissions.products.index` |
| Rail do vendedor | Não (correto: usa **Apresentar**) | `sales-app.products.present` |

**Conclusão:** Produtos existe em vários atalhos secundários, mas **não** está no rail principal entre Equipe e Financeiro.

Rail admin atual:

`Dashboard | Mapa | Campanhas | Clientes | Equipe | Financeiro`

Desejado (regra 8.2.12/8.2.13):

`… | Equipe | Produtos | Financeiro | …`

---

## 2. Visível para o usuário correto da Área da Empresa?

- Módulo nav `stock` exige `commissions.manage` (+ feature de plano `stock` quando o plano já tem features ativas).
- CRUD `ProductPolicy`: create/update/delete/manageStock → `commissions.manage` + mesmo `company_id`.
- Vendedor **não** acessa `commissions.products.*` (testado em `ManagerNavCommissionsIntegrationTest` e Sprint 8212/8213).

**OK** para quem tem `commissions.manage` (admin/gestor).  
**Risco menor:** tenants com plano que oculta `stock` podem perder os atalhos de nav secundária (legado sem features ativas ignora o filtro de plano).

---

## 3. Acesso direto entre Equipe e Financeiro?

**Não.** O rail primário pula de Equipe → Financeiro.

Atalhos 8.2.13 (aba em Equipe / botão em Financeiro) aproximam o acesso, mas **não** cumprem o requisito de item no rail principal.

---

## 4. Caminhos duplicados ou confusos?

**Sim — múltiplos entry points para o mesmo CRUD** (bom para descoberta, confuso para mental model):

1. Mais → Comercial → Produtos  
2. Mais → Empresa → Produtos (**duplicata**)  
3. Configurações → Produtos  
4. Equipe → aba Produtos  
5. Financeiro → botão Produtos  

Copy da listagem reforça “escondido em Configurações”:

> `Configurações → Produtos — catálogo…`  
> botão `← Configurações`

Isso conflita com a mensagem desejada: **Produtos = catálogo comercial da empresa**, não um subitem de settings.

---

## 5. CRUD de Produtos continua funcionando?

**Sim.** Rotas `commissions.products.*` + `ProductController` intactos.  
Cobertura: Sprint8212 (`company_can_create_edit_and_toggle_product`, `btn-new-product`).

---

## 6. Cadastro de produto disponível?

**Sim.** `+ Novo produto` (`#btn-new-product`) + empty-state CTA → `commissions.products.create`.

---

## 7. Ativar/desativar altera o catálogo do vendedor?

**Sim (por código).**

- Toggle: `commissions.products.toggle-status` → `ProductController::toggleStatus`
- Catálogo vendedor: `ProductCatalogService::activeCatalogForSeller()` filtra `status = active`
- Policy `view`: não-manager só vê produto ativo

Inativo some da apresentação / Sales App; ativo reaparece.

---

## 8. Vendedor vê somente ativos da própria empresa?

**Sim.** Confirmado em Sprint8211 / 8212 / 8213 (tenancy + active-only).  
`ProductPolicy` + `TenantScope` / `company_id`.

---

## 9. Administrador consegue visualizar a apresentação?

**Sim.** Na listagem admin: link **Ver apresentação** → `sales-app.products.present?product=`.  
Managers com `commissions.manage` passam em `authorizeSalesApp` do controller de apresentação.

---

## 10. Organização visual deixa claro “Produtos = catálogo comercial”?

**Parcialmente.**

- Título da página: **Produtos** — OK  
- Ações Editar / Ativar-Desativar / Ver apresentação — OK  
- Breadcrumb mental “Configurações → Produtos” + botão voltar para Settings — **enfraquece** a leitura de catálogo comercial de primeiro nível  
- Ausência no rail **Equipe | Produtos | Financeiro** — **gap principal**

---

## Resumo executivo

| Item | Status |
|------|--------|
| CRUD / cadastro / toggle / tenancy / seller catalog | OK — preservar |
| Apresentação admin + fluxo vendedor 8.2.13 | OK — preservar |
| Rail **Equipe \| Produtos \| Financeiro** | **FALTA** → Sprint 8.2.15 |
| Copy “Configurações → Produtos” | Confuso → 8.2.15 |
| Produtos duplicado em Comercial + Empresa (Mais) | Higiene → 8.2.15 |

**Nenhuma migration. Nenhuma mudança de arquitetura de produtos. Nenhuma alteração funcional nesta sprint.**
