# MAP-AUDIT

## Markers atuais

- Implementação: `coloredIcon()` → `.map-marker-dot` **círculo 14px** (`operational-map.js`, `maps/index.blade.php`).
- Cor: `MapMarkerColor::forStatus` / `ExpandorCommercialLayer`.
- Marcas internas: `R` (retorno), `×` (sem interesse).
- GPS usuário: `L.circleMarker` sky (distinto).

## Problema

Bolinha transmite “telemetria”, não “casa/ponto de venda”. Em zoom médio, status depende só da cor.

## Proposta final (1 padrão)

**House pin (silhueta casa / teardrop com telhado) preenchido com cor de status + marca R/×.**

| Critério | Círculo atual | House pin |
|----------|---------------|-----------|
| Affordance “residência” | Fraco | Forte |
| Densidade / cluster | Excelente | Bom (ícone um pouco maior) |
| Performance | Excelente (CSS div) | Bom se SVG/divIcon leve |
| Legibilidade mobile | Média | Melhor com stroke branco |
| Manutenção | Baixa | Média (1 template SVG) |

**Recomendação de produto:** adotar house pin; GPS permanece círculo/pulsante separado.

Não implementar nesta sprint.

## Clusters

- `commercial-cluster-bubble` + mix `N/I/C/V`.
- Cor dominante do cluster via `--cluster-color`.
- Manter informação C/V; alinhar cor ao mesmo mapa de status.
- Revisar tamanho em zoom baixo após novo pin.

## Legenda

Labels atuais (`MapCommercialGroup::legend()`):

| Label | Cor |
|-------|-----|
| Cliente / instalação | Verde `#22c55e` |
| Interessado | Azul `#3b82f6` |
| Retorno | Laranja `#f97316` + R |
| Sem interesse | Slate `#64748b` + × |
| Novo | Vermelho `#ef4444` |

Após house pin: swatches da legenda devem espelhar o mesmo ícone.

## Seller vs Manager

Mesmo mapa; Seller esconde filtros/equipe. Visual do chrome (sky CTAs) é compartilhado — inconsistente com brand do restante do app.
