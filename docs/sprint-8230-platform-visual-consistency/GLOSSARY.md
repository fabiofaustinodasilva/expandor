# GLOSSARY (UX copy)

Modelos/enums/database **não** foram renomeados.

| Termo visível | Significado | Não é |
|---------------|-------------|-------|
| **Ponto** | Representação territorial no mapa | Pessoa |
| **Imóvel / local** | Local físico | Relacionamento comercial |
| **Cliente** | Pessoa/empresa relacionada | Pin do mapa |
| **Visita** | Abordagem comercial | Login / presença |
| **Venda** | Conversão comercial | Visita |

## Mudanças de copy realizadas

| Onde | Antes | Depois |
|------|-------|--------|
| Drawer do mapa (eyebrow) | 📍 Residência | Ponto |
| Empty visitas | (variações “casa”) | “Você ainda não registrou visitas” / “próximo ponto” |
| Equipe drawer | fatos misturados | Seção **Acesso** (login vs atividade) + **Última visita** |

## Não alterado (regressão / JS)

| Onde | Copy | Motivo |
|------|------|--------|
| Day brief | “Casas visitadas hoje” | `MapsModuleTest` / `PilotSellerUxTest` |
| `operational-map.js` fallback | “Residência” | não mexer JS do mapa |
| `VisitHistoryPresenter::GPS_FALLBACK_LABEL` | Residência cadastrada pelo GPS | contrato de domínio |

## Futuro

Renomear “Casas visitadas hoje” → “Visitas hoje” junto com atualização dos testes de mapa.
