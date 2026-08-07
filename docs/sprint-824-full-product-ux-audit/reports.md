# Relatórios e Financeiro — auditoria UX

## Relatórios

**View:** `reports/index.blade.php`  
**Rota:** `reports.index` → `/relatorios`  
**Shell:** operational + `x-client.*`

### Clareza vs expectativa

O header diz que a análise detalhada “foi organizada aqui”. Na prática
a página é um **hub de atalhos** para Dashboard 30d, Comissões, CRM e
Equipe — **sem funil, gráficos ou export** (**P0**).

Isso é o maior gap de confiança pós-8.2.2: o Dashboard ficou slim
prometendo que a profundidade iria para Relatórios; a profundidade
**não foi reconstruída**.

| # | Achado | Sev |
|---|--------|-----|
| 1 Clareza | Texto promete análise; UI entrega links | P0 |
| 2 Excesso | Não | — |
| 3 Escondido | Análise real inexistente / ainda no passado | P0 |
| 7 Unificar | Ou virar “Atalhos de análise” ou reports reais | P0 |
| 10 Nomenclatura | “Relatórios” superestima | P0 |
| 19 Mobile | Grid ok | — |

### Oportunidades

| Opção | Prós | Contras | Estimativa |
|-------|------|---------|------------|
| A) Renomear para “Análises / Atalhos” + copy honesta | Rápido, honestidade | Não entrega insight | 0,5 d |
| B) Restaurar funil/gráficos aqui (views only se dados já existem no dashboard service) | Cumpre promessa | Pode exigir controller — fora de audit; planejar sprint | 3–8 d |
| C) Relatórios exportáveis (CSV) | Valor gestor | Backend | sprint própria |

**Recomendação de produto:** A imediato + B na sprint seguinte de UX
com escopo de views/support permitido.

---

## Financeiro / Comissões (campo)

**Views:** `commissions/index.blade.php`, `commissions/products/*`  
**Rail admin:** label **Financeiro** → página **Comissões**  
**Rail seller:** **Comissão**

| Achado | Sev |
|--------|-----|
| Label rail ≠ título da página (Financeiro vs Comissões) | P1 |
| Filtros sempre abertos (não colapsáveis como dashboard) | P1 |
| Summary cards `.card` crus ≠ `metric-card` | P2 |
| Products index com emoji 📦 | P1 |
| Sem bulk approve (muitos cliques gestor) | P1 |
| Dualidade com `crm.commissions` (regras) | P1 |

### Oportunidades Financeiro

| Ideia | Impacto | Estimativa |
|-------|---------|------------|
| Hub Financeiro com tabs: Comissões \| Estoque \| Regras | Alto | 2–3 d |
| Filtros em `<details>` colapsável | Médio | 0,25 d |
| Bulk actions UI (se API já permite batch — só UI) | Alto | 1–2 d |
| Remover emoji produtos | Médio | 0,25 d |
| Título da página = label do rail | Médio | 0,25 d |

### Fluxo gestor — aprovar comissões

Estimativa atual: filtrar → abrir linha → aprovar → repetir (**N×3–4
cliques**). Ideal: seleção + “Aprovar selecionadas” (**~3 cliques
totais**).
