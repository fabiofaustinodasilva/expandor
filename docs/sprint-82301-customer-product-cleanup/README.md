# Sprint 8.2.30.1 — Lista de clientes + exclusão segura

Corrige organização de **Clientes** (lista compacta) e **Produtos** (excluir só o que nunca foi usado).

Integridade histórica > limpar a tela.

## Regras

| Entidade | Sem histórico | Com histórico |
|----------|---------------|----------------|
| Cliente (`Property`) | Soft-delete (some o ponto do mapa/CRM; **não** `forceDelete`) | Bloqueado |
| Produto | Hard-delete (já existia) | Só desativar |

**Zero migrations.** Sem SoftDeletes novo em Product.

## Docs

- [AUDIT.md](./AUDIT.md)
- [CUSTOMER-LIST.md](./CUSTOMER-LIST.md)
- [CUSTOMER-DELETION.md](./CUSTOMER-DELETION.md)
- [PRODUCT-DELETION.md](./PRODUCT-DELETION.md)
- [HISTORY-INTEGRITY.md](./HISTORY-INTEGRITY.md)
- [TENANCY.md](./TENANCY.md)
- [TEST-REPORT.md](./TEST-REPORT.md)
- [UX-CHECKLIST.md](./UX-CHECKLIST.md)
- [CHANGELOG.md](./CHANGELOG.md)

## Branch / commit

- `feature/sprint-8230-1-customer-product-cleanup`
- `feat: improve customer list and safe product cleanup`
- Sem push / sem merge
