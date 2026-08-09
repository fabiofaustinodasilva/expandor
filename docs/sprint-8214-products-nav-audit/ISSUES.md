# ISSUES — Sprint 8.2.14 (somente registro)

## ISSUE-8214-01 — Produtos ausente do rail principal da gestão

**Severidade:** Alta (descoberta / UX da Área da Empresa)  
**Status:** Aberto — implementar na **Sprint 8.2.15**  
**Não corrigido em 8.2.14** (auditoria apenas).

### Descrição

`ClientNav::adminRail()` não inclui **Produtos** entre **Equipe** e **Financeiro**.

Ordem atual (trecho relevante):

```
team → Equipe → operations.team
commissions → Financeiro → commissions.index
```

Ordem desejada (regra 8.2.12/8.2.13):

```
Equipe | Produtos | Financeiro
```

### Evidência

- Arquivo: `app/Support/ClientArea/ClientNav.php` (`adminRail`)
- Teste legado ainda documenta decisão antiga:  
  `ManagerNavCommissionsIntegrationTest` — “Produtos/Estoque agora vive apenas em Mais opções”

### Impacto

Admin precisa descobrir Produtos via Mais, Configurações, ou atalhos dentro de Equipe/Financeiro — não no rail principal.

### Direção de fix (8.2.15)

- Inserir item `stock` / label **Produtos** / route `commissions.products.index` no `adminRail` entre Equipe e Financeiro.
- Atualizar testes de nav (ManagerNav + Sprint 8214/8215).
- **Não** criar CRUD paralelo.
- **Não** remover atalhos existentes sem revisão (podem permanecer como redundância útil).

---

## ISSUE-8214-02 — Copy reforça “Configurações → Produtos”

**Severidade:** Média (mensagem / mental model)  
**Status:** Aberto — 8.2.15

### Descrição

`resources/views/commissions/products/index.blade.php` apresenta:

- subtítulo “Configurações → Produtos…”
- botão “← Configurações”

Isso sugere que Produtos é subpágina de settings, não catálogo comercial de primeiro nível.

### Direção de fix (8.2.15)

- Reescrever copy para “Catálogo comercial da empresa” (ou equivalente).
- Trocar/remover voltar exclusivo para Settings (opcional: voltar ao Dashboard / Equipe / rail).

---

## ISSUE-8214-03 — Produtos duplicado em seções Mais (Comercial + Empresa)

**Severidade:** Baixa (higiene de navegação)  
**Status:** Aberto — 8.2.15

### Descrição

`ClientNav::rawSections()` lista **Produtos** em Comercial e novamente em Empresa, ambos para `commissions.products.index`.

### Direção de fix (8.2.15)

Manter **um** item canônico (preferência: Comercial ou Empresa, alinhado à mensagem “catálogo comercial”) e remover a duplicata.

---

## Não-issues (verificar OK — não abrir ticket)

| Tema | Resultado |
|------|-----------|
| CRUD + Novo produto | OK |
| Toggle status → catálogo seller | OK |
| Seller só ativos / própria empresa | OK |
| Admin Ver apresentação | OK |
| Fluxo 8.2.13 Apresentar → Detalhes → Contratar | OK — preservar |
| ProductPolicy / tenancy / billing / comissão | OK — não alterar |
