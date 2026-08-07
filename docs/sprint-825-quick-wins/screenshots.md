# Screenshots / Antes → Depois (Sprint 8.2.5)

Capturas ao vivo dependem de sessão autenticada no ambiente local.
Abaixo, o registro visual **conceitual** validado via HTML/assertões de teste
(2026-08-06). Para capturar no browser: login → percorrer as URLs.

## 1. Mais → Configurações (QW-01)

| Antes | Depois |
|-------|--------|
| Configurações só por URL/back-link | Item **Configurações** na seção Empresa do Mais |
| URL | `/operacao/mais` → link `operations.settings` |

## 2. Atalhos de análise (QW-02)

| Antes | Depois |
|-------|--------|
| Título “Relatórios” prometendo funil/gráficos | **Atalhos de análise** + alerta info honesto |
| URL | `/relatorios` |

## 3. Mapa — Novo ponto (QW-03)

| Antes | Depois |
|-------|--------|
| “Nova oportunidade” nos CTAs / modal | **Novo ponto**; CTAs sem 👣 |
| URL | `/map` |

## 4. Dashboard seller (QW-04 + QW-12)

| Antes | Depois |
|-------|--------|
| H1 “Dashboard” com 6 CTAs | H1 **Resultado**; period seg; Abrir mapa; Mais |
| URL | `/dashboard` (papel vendedor) |

## 5. Financeiro (QW-06 + QW-07)

| Antes | Depois |
|-------|--------|
| Título “Gestão de comissões”; filtros abertos | Título **Financeiro**; filtros colapsados |
| URL | `/comissoes` |

## 6. Equipe (QW-11)

| Antes | Depois |
|-------|--------|
| Dual header + aba Usuários | Um `page-header` + aba **Membros** |
| URL | `/operacao/equipe` |

## 7. Campanhas overflow (QW-10)

| Antes | Depois |
|-------|--------|
| 4–5 botões por linha | Visitas + menu **⋯** |
| URL | `/campaigns` |

## 8. Users banner (QW-14)

| Antes | Depois |
|-------|--------|
| CRUD sem contexto | Alert → link Equipe |
| URL | `/users` |

## Como capturar localmente

```text
1. php artisan serve (ou stack habitual)
2. Login admin / seller
3. Abrir as URLs acima
4. Salvar PNGs em docs/sprint-825-quick-wins/assets/ (opcional)
```

Validação automatizada substitui screenshots nesta sprint:
`artisan test --filter=Sprint825` (7 passed).
