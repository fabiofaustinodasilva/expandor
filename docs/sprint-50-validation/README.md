# Sprint 5.0 — Relatório de validação (Manager operacional)

**Data:** 2026-08-02 (atualizado 2026-08-03 — correção de permissões)  
**Ambiente:** `http://127.0.0.1:8000` (demo UNICA)  
**Escopo:** validação + correção **somente** do seed de permissões `commissions.*`  
**Evidência automatizada:** suite Commissions OK  
**Evidência UI:** Manager + Seller após `RolePermissionSeeder`

---

## Sumário executivo

| Área | Antes (live) | Depois do seed |
|---|---|---|
| Schema (products, sales_commissions, stock_movements) | OK | OK |
| Nav Manager (Comissões + Produtos) | Oculto (`perm_rows=0`) | Visível no op-rail |
| Seller Minha comissão / botão 💰 | 403 / ausente | Visível e acessível |
| Segurança Seller → Produtos | 403 OK | 403 OK |
| Fluxo Contratou (UI produto) | OK | OK (não alterado) |

**Veredito:** gap era **só seed no banco demo**. Código/Sprint 5.0 já estavam corretos. Após `RolePermissionSeeder` + migration idempotente, Manager e Seller passam na validação visual.

---

## Correção aplicada

### 1. `RolePermissionSeeder` (já continha os slugs)
- `commissions.manage`
- `commissions.view_self`

Associações:
| Papel | manage | view_self |
|---|---|---|
| Administrator | sim | sim |
| Manager | sim | sim |
| Supervisor | sim | sim |
| Seller | não | sim |

### 2. Seed no demo
```bash
php artisan db:seed --class=RolePermissionSeeder
```

### 3. Seeder + migration idempotentes (novos)
- `database/seeders/EnsureCommissionPermissionsSeeder.php` — `updateOrCreate` + `syncWithoutDetaching` (não remove grants antigos)
- `database/migrations/2026_08_03_012726_ensure_commission_permissions.php` — aplica o seeder no `up()` (rollback vazio)

```bash
php artisan migrate --path=database/migrations/2026_08_03_012726_ensure_commission_permissions.php
# ou
php artisan db:seed --class=EnsureCommissionPermissionsSeeder
```

### 4. Validação no banco (após seed)

```
perm_rows=2
manager@unicanetwork.demo  manage=1  self=1
seller@unicanetwork.demo   manage=0  self=1
```

---

## Validação visual (após correção)

| Papel | Esperado | Resultado | Print |
|---|---|---|---|
| Manager | Rail: Comissões + Produtos | OK | `07-manager-comissoes-produtos-rail.png` |
| Manager | Página Gestão de comissões + link Produtos/Estoque | OK | `08-manager-gestao-comissoes.png` |
| Seller | Rail: Comissão + 💰 Minha comissão | OK | `04-seller-comissao-visivel.png` |
| Seller | Página Minha comissão (sem aprovar/pagar) | OK | `06-seller-minha-comissao.png` |
| Seller | Produtos → 403 | OK | `05-seller-produtos-403.png` |

---

## 1. Manager — painel operacional

### Live (após seed)
Rail com **Comissões** e **Produtos**; dashboard com **💰 Comissões**; `/comissoes` abre **Gestão de comissões**.

---

## 2. Produtos

| Fluxo | Status |
|---|---|
| Manager acessa Produtos / Estoque | OK (UI + permissão) |
| Seller → Produtos | **403** |

Regras de estoque/venda **não foram alteradas**.

---

## 3. Fluxo Seller — comissão

| Fluxo | Status |
|---|---|
| Minha comissão no rail / dashboard | OK |
| `/comissoes` como Seller | OK (título “Minha comissão”) |
| Aprovar / pagar | Sem ações de gestão na UI Seller |

---

## 4. Segurança

| Caso | Status |
|---|---|
| Seller → Produtos | **403** |
| Seller `manage=false` / `self=true` | Confirmado no banco |
| Manager `manage=true` / `self=true` | Confirmado no banco |

---

## Prints

| Arquivo | Conteúdo |
|---|---|
| `01-seller-produtos-403.png` | Seller bloqueado em Produtos (pré-fix) |
| `02-seller-dashboard-sem-comissao.png` | Seller sem 💰 (pré-fix) |
| `03-seller-mapa-produto-contratado.png` | Mapa Seller — Contratou com produto |
| `04-seller-comissao-visivel.png` | Seller com Comissão + 💰 Minha comissão |
| `05-seller-produtos-403.png` | Seller → Produtos ainda 403 |
| `06-seller-minha-comissao.png` | Seller em Minha comissão |
| `07-manager-comissoes-produtos-rail.png` | Manager rail com Comissões + Produtos |
| `08-manager-gestao-comissoes.png` | Manager Gestão de comissões |

---

## Conclusão

Permissões `commissions.*` restauradas no demo. Nenhuma regra de comissão, estoque ou fluxo de venda foi alterada — apenas seed/migration de permissões.
