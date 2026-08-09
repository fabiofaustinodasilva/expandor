# GOOGLE-MAPS-AUDIT

**Não implementar nesta sprint.** Consultar documentação oficial Google antes de codificar (preços/ToS mudam).

## Não é “trocar URL do tile”

Termos Google **proíbem** consumir tiles Google fora da API oficial. Leaflet FAQ: acesso a tiles Google só via Google Maps API (ex.: GoogleMutant com lag).  
Portanto **não** tratar Google como drop-in de `tile.openstreetmap.org`.

## Opções técnicas

| Abordagem | Leaflet? | Notas |
|-----------|----------|-------|
| **A. Maps JavaScript API** (engine Google) | Não (substitui Leaflet no canvas) | Full features; Places/Street View; billing Dynamic Maps |
| **B. Leaflet + GoogleMutant** | Sim | Overlay Google sob Leaflet; glitches possíveis; ainda exige Maps JS API + billing |
| **C. Map Tiles API** | Possível com sessão/token | Billing por tile; políticas rígidas de cache; ToS específicos |

### Recomendação de produto (auditoria)

**Fase 1 (mapa visual premium):** avaliar **A** ou **B** com spike técnico curto.  
Preferência de arquitetura Expandor: manter **camada comercial** (markers/clusters) isolada — se migrar engine, reimplementar markers na API Google **ou** usar Mutant mantendo Leaflet markers.

**Não assumir** que satélite Esri → Google é só URL.

## APIs Google relevantes (futuro)

| API | Uso Expandor | Prioridade |
|-----|--------------|------------|
| Maps JavaScript API | Mapa visual manager/seller | P0 se Google Maps UI |
| Geocoding | GPS → endereço → cidade/setor (Central Território) | P1 |
| Places / Autocomplete | Busca endereço | P2 |
| Routes / Directions | Rotas campo | P2 |
| Street View | Futuro | P3 |
| Map Tiles API | Alternativa tiles | Avaliar só com jurídico/ToS |

## Keys: browser vs server

| Tipo | Onde | Restrição típica | É “secret”? |
|------|------|------------------|-------------|
| Browser key (Maps JS) | Injetada no front (referrer HTTP) | Domínios Expandor / domínio custom | **Pública por design**; restringir referrer; **não** tratar como secret server-side puro |
| Server key (Geocoding/Routes) | Backend Laravel | IP / sem referrer | **Secret** — encrypted at rest; nunca no Blade |

Empresa configura no GCP **o próprio projeto** (billing dela). Expandor só armazena a key que a empresa colar (browser e/ou server conforme capability).

## Billing / responsabilidade

- Google cobra o **projeto GCP da empresa**.  
- Expandor cobra via **plano** o direito de usar a integração.  
- Confirmar preços/créditos atuais na doc oficial antes do go-live.

## Capacitor futuro

Provável necessidade de keys/Android/iOS packages separados + Maps SDK nativo — ver CAPACITOR-READINESS.md.

## Test connection (proposta)

Server-side: chamada mínima (ex. Geocoding ping ou Maps Static) **ou** validação de restrições conhecidas; mapear erros: key inválida, API não habilitada, billing, referrer.  
Não implementar agora.
