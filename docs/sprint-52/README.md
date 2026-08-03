# Sprint 5.2 — Venda Inteligente (Cadastro em duas etapas)

**Data:** 2026-08-03  
**Princípio:** prospecção rápida (&lt; 30s). Dados completos do cliente **somente** em “Venda realizada”.

---

## Fluxo

```
Chega na residência → Primeiro atendimento → Resultado
  • Sem interesse / Interessado / Retornar / Não encontrado → salva imediatamente
  • Venda realizada → abre “Finalizar venda” → valida campos da empresa → Visit → Sale → Comissão → Estoque
```

---

## Arquitetura

| Peça | Papel |
|---|---|
| `visits` | Centro do sistema (status `installation_requested`) |
| `residents` | Pessoa (upsert contato principal) + coluna `whatsapp` |
| `sales` | Dossiê 1:1 com a visita (`negotiated_amount`, `notes`, `attributes.rg`) |
| `sales_commissions` / `stock_movements` | Inalterados |
| `company_settings` → `sale.required_fields` | Checklist dinâmico por empresa |

RG fica em `sales.attributes.rg` (sem coluna extra em residents; só `whatsapp` foi adicionada).

### Defaults obrigatórios
`name`, `phone`, `product`, `negotiated_amount`

### Separação de notas
- `visit.notes` — observação da visita  
- `sales.notes` — observação da venda (`sale_notes` no request)

---

## UI

- **Configurações → Venda** — checkboxes de campos obrigatórios  
- **Mapa / First Approach / Agenda** — bloco “Finalizar venda” só ao escolher Venda realizada  
- Partial: `resources/views/partials/sale-finalize-fields.blade.php`

## Mobile

API aceita campos de venda de forma **aditiva**. App antigo sem payload de venda continua compatível; tela Finalizar venda no app = sprint futura.

---

## Arquivos principais

- `app/Support` — (terminologia 5.1)  
- `app/Domains/Sales/SaleFields/*` — policy + keys + resolver  
- `app/Domains/Sales/Models/Sale.php`  
- `app/Domains/Visits/Services/VisitService.php` — upsert + create Sale  
- `database/migrations/2026_08_03_200001_*` / `200002_*`  
- Controllers/Requests/Views mapa, agenda, settings  

---

## Compatibilidade

Não quebra: mapa, agenda, histórico, dashboard, comissões, estoque, First Approach, APIs mobile (aditivo).
