# AUDIT — Sprint 8.2.9

## Superfície

`/map` · `resources/views/maps/index.blade.php` · `public/js/operational-map.js` · `public/js/map-provider.js`

## Achados (pré-refino)

1. **Meu Local abria formulário** — conflita com regra “só localizar”.
2. **GPS** — uma mensagem única para deny/timeout/unsupported.
3. **Formulário** — sem “Voltar ao mapa”; Salvar sticky frágil; risco de duplo envio.
4. **Legado** — `openEmptySpot` / listeners empty-spot mortos.
5. **Tips/brief** — ainda diziam “Meu Local abre cadastro”.
6. **Cores return vs no_interest** — indistintas no mapa (já notado na 8.2.8).

## Backend necessário — cores return / sem interesse

### Onde a cor é definida

1. **`app/Domains/Maps/Enums/MapCommercialGroup.php`**
   - `RETURN_LATER` e `NO_INTEREST` → grupo `VISITED`
   - `VISITED` → `MapMarkerColor::YELLOW` (`#eab308`)
2. **`app/Domains/Maps/Services/MapQueryService::toMarker`** — envia `color` + `commercial_group` no payload.
3. **`public/js/map-provider.js`** — fallback client: `return_later || no_interest` → `visited` (mesmo amarelo).
4. **`public/js/operational-map.js`** — pinta o pin com `marker.color` do payload.

### Camada que controla

Contrato comercial do mapa (`MapCommercialGroup`), não o formulário nem VisitStatus isolado. No CRM, labels já diferenciam (🟠 retorno / 🔴 sem interesse); o **mapa colapsa** ambos em “Visitado”.

### Alteração mínima necessária (NÃO feita nesta sprint)

- Separar grupos no enum (ex.: `RETURN` e `NO_INTEREST`) **ou**
- Manter grupo mas `MapMarkerColor::forStatus` por status individual;
- Atualizar legenda Blade + `ExpandorCommercialLayer.groups` no JS;
- Possível impacto em filtros comerciais por grupo.

### Impacto

- Visual do mapa / legenda / filtros por grupo comercial.
- Sem mudança de `VisitStatus` / `PropertyStatus` no banco se só a camada de apresentação mudar.
- Testes: MapsModule (commercial group), marker payload, filtros.

### Decisão 8.2.9

**Documentado apenas.** Sem implementação silenciosa de backend.

## O que NÃO foi alterado

Auth, tenancy, permissões, billing, Mercado Pago, migrations, controllers/services de create/first-approach, enums de status, Sales App `/app` (hub permanece o mapa).

## Navegação

Menu + Mais no shell operacional permanecem (herança 8.2.6). Sem nova duplicação de mapa. Seller não ganha links admin no chrome do mapa. Página “Mais” continua filtrada por permissões existentes (fora do escopo remover módulos já permitidos).
