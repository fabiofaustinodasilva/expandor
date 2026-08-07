# Roadmap — Sprint 8.2.2 (implementação)

> Só após **aprovação** desta Sprint 8.2.1.

## Objetivo 8.2.2

Implementar a Fase 1 da refatoração de UX da Área do Cliente **sem** novas features de negócio, **sem** mudar banco/API/integrações/permissões seed.

## Escopo IN

1. Aplicar [glossary.md](./glossary.md) em menus e títulos principais.  
2. Implementar árvore de [navigation.md](./navigation.md) no shell operacional (+ ajustar Mais).  
3. Dashboard slim conforme [dashboard.md](./dashboard.md).  
4. Hub Relatórios mínimo (mover widgets; pode ser views relocadas).  
5. `NavVisibility` (perm ∧ flag ∧ plan) — [feature-flags.md](./feature-flags.md).  
6. Unificar entrada Equipe (redirect `/users` → hub).  
7. Registrar Policies faltantes no Gate — [security.md](./security.md).  
8. Lazy ativação no Dashboard — [performance.md](./performance.md).  
9. Limpeza UX (emoji, breadcrumbs básicos) — [ux.md](./ux.md).  
10. Testes de regressão: mapa → visita → comissão; login roles; menus por plano.

## Escopo OUT

- Novos módulos / campos  
- Migrations / mudanças de schema  
- Alterar Mercado Pago, webhooks, provisioning  
- Alterar `RolePermissionSeeder` (salvo se estritamente necessário p/ reports UI usando perms já existentes)  
- Remover código de módulos (só esconder/reparentar)  
- Unificar onboarding Setup+SaaS (reservar 8.2.3)  
- SPA / Livewire rewrite  

## Ordem de execução sugerida

| Semana lógica | Entrega |
|---------------|---------|
| 1 | Glossário + nav shell + labels |
| 1–2 | Dashboard slim + Relatórios hub mínimo |
| 2 | NavVisibility + testes por plano |
| 2 | Equipe unificada (entrada) |
| 3 | Policies Gate + perf P0 + polish UX |
| 3 | QA regressão + docs release |

## Critérios de aceite 8.2.2

- [ ] Zero “Clientes / Pontos” na UI  
- [ ] Um menu primário documentado por role  
- [ ] Dashboard ≤ widgets oficiais (workspace ready)  
- [ ] Módulo off no plano ⇒ ausente no menu  
- [ ] `/users` não aparece como item paralelo a Equipe  
- [ ] Fluxo mapa→visita→comissão verde nos testes  
- [ ] Nenhuma migration nova  
- [ ] API mobile inalterada  

## 8.2.3+ (depois)

- Onboarding único  
- Fundir de fato controllers Users/Team se ainda houver gap  
- Performance P1 mapa com baseline  
- Remoção segura de código morto confirmado  
- Ajustes CRM opcional por segmento
