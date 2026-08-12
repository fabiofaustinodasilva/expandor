# WEB × MOBILE — Paridade do fluxo de campo (EXP Vendedor)

Auditoria concluída em 2026-08-12. Web fonte: mapa operacional (`/map`, `operational-map.js`, `RegisterFirstApproachAction`, `VisitService`).

## Matriz resumida

| Recurso | Web | Mobile antes | Mobile depois | Service/API compartilhada |
|---------|-----|--------------|---------------|---------------------------|
| Novo imóvel | Endereço + contato + outcome na 1ª abordagem | Form genérico com Status=Novo | Endereço + contato apenas; visita na sequência | `PropertyService` / `POST /api/mobile/v1/points` |
| Separar imóvel × visita | Seller: modal único com chips | Create + visit sheet (2 passos) | Mantido 2 passos UX; visita abre após salvar | `RegisterVisitAction` |
| Campanha | Auto se 1 ativa; select se 2+ | Select sempre; bootstrap sem contexto | Contexto no bootstrap; label fixa ou select | `RegisterFirstApproachAction::resolveCampaign` |
| Resultados visita | 5 chips VisitStatus | 5 chips (parcial) | 5 chips alinhados + cores MapMarkerColor | `VisitStatus` enum |
| Interessado | Notas recomendadas | Notas apenas | Idem web (sem picker de plano — web também não expõe) | `VisitService::register` |
| Retornar depois | Data/hora + agenda | Data/hora | Idem + concluir retorno pela Agenda | `scheduleFollowUp`, `CompleteFollowUpAction` |
| Venda realizada | PDV multi-produto + policy | Nome/telefone/1 produto | Carrinho multi-produto + campos da policy | `SaleFieldsPolicyResolver`, `ProductCatalogService::sellableOptions` |
| Não encontrado | Fluxo rápido | OK | OK | `VisitService` |
| Sem interesse | Fluxo rápido | OK | OK | `VisitService` |
| Produtos/planos | sellableOptions ativos | Lista simples | sellable + qty + preço servidor | `GET /api/mobile/v1/products` |
| Produto desativado | Some do catálogo | OK (filtro backend) | OK | `ProductCatalogService` |
| Comissão | Servidor calcula | OK | OK | `CommissionAwardedPayload` |
| Marker/pin | reload markers | updateMarker local | Idem | `MapQueryService` |
| GPS visita | lat/lng no POST | Não enviava | Envia coords do pin ou GPS | `StoreVisitRequest` |
| Apresentar produtos | Sales App separado | PresentationScreen | Preservado (não mistura com visita) | `GET /products` |

## Fluxo web auditado

1. **Novo ponto (seller):** tap mapa → `#point-modal` → endereço/contato → chips “Como foi a abordagem?” → `POST /map/first-approach` → `RegisterFirstApproachAction`.
2. **Visita em imóvel existente:** marker → drawer → “Registrar visita” → `#visit-modal` → `POST /map/campaigns/{id}/visits` → `RegisterVisitAction`.
3. **Campanha:** `activeCampaignsFor()` — 0 = erro, 1 = auto, 2+ = obrigatório selecionar.
4. **Venda:** bloco `sale-finalize-fields` — campos obrigatórios por empresa + carrinho `items[]`.
5. **Retorno:** `follow_up_at` → `VisitService::scheduleFollowUp` → agenda `/follow-ups`.

## Fluxo mobile alvo

```
MAPA → selecionar local → NOVO IMÓVEL (mínimo) → Salvar
  → REGISTRAR VISITA → chips de resultado → campos condicionais → 2xx
  → marker atualizado + agenda/comissão quando aplicável
```

## Gaps fechados nesta entrega

- Removido **Status = Novo** do formulário de criação (estado técnico `PropertyStatus::NEW` no servidor).
- Bootstrap expõe `campaign_context`, `active_campaign_id`, `sale_fields`.
- Visitas validam campanha via `RegisterFirstApproachAction::resolveCampaign` (mesma regra web).
- Venda mobile usa policy de campos + carrinho multi-produto.
- Agenda: botão **Registrar retorno** → visit sheet → `completeFollowUp`.
- Coordenadas enviadas na visita quando disponíveis.

## Gaps conscientemente iguais ao web

- **Interessado:** web mapa não exibe seletor de plano/produto de interesse (apenas notas). Mobile mantém paridade.
- **Primeira abordagem one-shot:** web usa transação única; mobile mantém 2 requests (property + visit) com UX sequencial equivalente.

## Arquivos principais

| Área | Arquivos |
|------|----------|
| Web mapa | `resources/views/maps/index.blade.php`, `public/js/operational-map.js` |
| Domínio | `VisitService`, `RegisterFirstApproachAction`, `SaleFieldsPolicyResolver` |
| Mobile API | `MobileSellerOpsService`, `PointVisitOpsController` |
| Mobile UI | `bootstrap-shell.js`, `sale-cart.js`, `visit-outcomes.js`, `prepare-capacitor-shell.mjs` |
