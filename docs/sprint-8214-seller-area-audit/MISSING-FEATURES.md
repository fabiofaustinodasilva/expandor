# MISSING-FEATURES

## P0

### Retorno sem FollowUp na Agenda
**Problema:** status retorno sem data não agenda.  
**Impacto:** perde compromisso verbal.  
**Solução:** exigir data (mínimo dia); default amanhã.  
**Reuso:** `point-return-block`, `VisitService::scheduleFollowUp`.

### Visão “Hoje” no mapa
**Problema:** brief só agrega métricas.  
**Impacto:** não sabe próxima ação.  
**Solução:** chip “N retornos hoje” → Agenda.  
**Reuso:** day metrics + `follow-ups.index`.

## P1

### Unificar Agenda / Sales App Retornos
**Problema:** duas listas.  
**Solução:** um destino; deep-link.  
**Reuso:** `FollowUpController`.

### Glossário único (Ponto / Cliente)
**Problema:** confusão.  
**Solução:** copy pass no mapa seller.  
**Reuso:** `CommercialTerminology`.

### Prefetch catálogo
**Problema:** 1º GET present na rua.  
**Solução:** idle prefetch no mapa.  
**Reuso:** `ProductCatalogService`.

## P2

### Histórico compacto no drawer
Timeline 3 linhas no ponto.

### Contratar in-place (sem reload mapa)
Maior esforço JS.

## FUTURO

- Push/lembrete de retorno  
- Modo offline completo do deck  
- Treino contextual ligado ao produto (sem duplicar Academia)
