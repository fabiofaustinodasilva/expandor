# Sales — Pontos, Visitas, Agenda, Carteira e Sales App

Abrange o fluxo comercial de campo e a carteira de clientes (não CRM
pipeline).

## Pontos (`properties`)

**Views:** `sales/properties/properties/*`  
**Rotas:** `/properties`  
**Shell índice:** `layouts.app` (fora do rail operacional)

| Achado | Sev |
|--------|-----|
| Índice fora do shell operacional | P1 |
| Empty copy “cliente / ponto” viola glossário | P1 |
| Sem busca forte no índice | P2 |
| Status via página — drawer bastaria | P2 |
| Create multi-campo — wizard útil | P2 |

**Oportunidade:** índice em operational + drawer de status + empty
“Nenhum ponto”.

## Clientes / carteira (`customers`)

**Views:** `customers/index|show`  
**Rotas:** `/clientes`

| Achado | Sev |
|--------|-----|
| Index já com crud-toolbar (8.2.3) — bom | — |
| Show com barra de ações emoji | P1 |
| Descrição “CRM comercial” confunde com `/crm` | P2 |
| Unificar com leads? Não — papéis diferentes; só clarificar labels | P2 |

## Agenda / retornos (`follow-ups`)

**Views:** `visits/follow-ups/index.blade.php`  
**Shell:** operational + modal custom

| Achado | Sev |
|--------|-----|
| Modal de conclusão longo (venda) | P1 |
| CSS ≠ `client-modal` | P2 |
| Bom uso de modal vs página (positivo) | — |

## Minhas visitas (`operations.my-visits`)

| Achado | Sev |
|--------|-----|
| Emojis 👁💬 | P1 |
| Entrada fraca no rail (não é item primário) | P2 |
| Deveria ser aba da Agenda | P1 |

## Sales App (`/app`)

**Views:** `sales-app/*`  
**Nav:** Início · Campanhas · Retornos · Academia

| # | Achado | Sev |
|---|--------|-----|
| 1 | Terceiro shell para as mesmas jobs do mapa/agenda | P1 |
| 2 | Copy “Status do cliente” para property | P2 |
| 3 | “Abrir painel completo” joga no operational — choque | P2 |
| 4 | Brand “EXPANDOR Campo” hardcoded | P3 |
| 5 | Melhor mobile do produto (positivo) | — |
| 6 | Sem skip-link / client-ui pleno | P2 |

### Fluxo vendedor (completo)

```
Mapa (web) ──┐
Agenda ──────┼──► registrar visita / retorno ──► Comissão
Sales App ───┘
```

Três portas, um destino. **Produtividade cai na escolha**, não na
execução.

### Oportunidades Sales App

| Ideia | Impacto | Estimativa |
|-------|---------|------------|
| Posicionar como PWA/skin do mapa+agenda (mesmo outcome component) | Alto | 5–8 d |
| Alinhar glossário Pontos | Alto | 0,5 d |
| Progresso Academia no Início | Médio | 1 d |
| ThemeService no brand do campo | Baixo | 0,5 d |

## Visitas avulsas (`visits/*` em app)

Detalhe de visita em shell clássico — ok como deep link; garantir
breadcrumb de volta à Agenda/Mapa (**P2**).

## Resumo de cliques (vendedor, dia típico)

| Tarefa | Caminho atual | Cliques est. | Ideal |
|--------|---------------|--------------|-------|
| Registrar visita | Mapa → pin → sheet | 3–5 | 3 |
| Completar retorno | Agenda → Completar → modal | 4–7 | 3–4 |
| Ver comissão | Rail Comissão | 1–2 | 1 |
| Entrar pelo Sales App e voltar ao painel | App → Painel | 2 + reorientação | 1 (deep link contextual) |
