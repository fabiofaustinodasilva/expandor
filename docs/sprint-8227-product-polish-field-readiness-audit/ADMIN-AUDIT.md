# ADMIN-AUDIT

## Dashboard

- Cards/métricas existem; hierarquia varia por permissão.
- Admin novo: entende “como está a operação?” de forma parcial — depende de widgets ativos.
- P1: hierarquia “hoje → visitas → vendas → comissões”.
- P2: empty states com CTA.

## Campanhas / território

- Catálogo cidade/UF/setor melhorou em sprints anteriores.
- Verificar labels “setor/área” vs “campanha” em onboarding.
- P2: datas/status com timezone correto após P0.

## Clientes / Pontos

Glossário canônico (`docs/sprint-821-client-refactor/glossary.md`):

| Termo | Significado |
|-------|-------------|
| Ponto | Unidade no mapa |
| Morador | Pessoa no ponto |
| Cliente | Carteira / customer |

**Drift atual:** drawer “Residência”, JS “casa”, search “cliente”.

Proposta: 1 PR só de copy (sem schema).

## Produtos

- Comissão fixa/% alinhada (8.2.23).
- Apresentação seller (deck) visualmente distinta do CRM — aceitável.
- P2: preview/branding do deck.

## Comissões / Financeiro

- Coluna Valor da venda (8.2.23.1) ok.
- Futuro (não implementar agora): Total vendido / Total comissão / Pendente / Pago.
- Mobile tabela: P1 polish scroll horizontal.

## Integrações

- Google Maps: conectar/testar/fallback ok funcionalmente.
- Linguagem ainda semi-técnica para dono de PME.
- P2: copy “plano permite Google Maps” em linguagem de benefício.

## Auth

- Login + forgot + reset (8.2.24) ok.
- Sessão substituída (8.2.25) ok.
- P2: polish visual login já branded; manter lógica.

## Super Admin

- Empresas / planos / features / assinatura.
- Consistência visual com Área da Empresa: parcial (mesmo layout operacional).
- P2: alinhamento de cards/tables.
