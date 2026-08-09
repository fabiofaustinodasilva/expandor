# CONTRACT-AUDIT

## Fluxo

Deck **Contratar** → `/map?contract_product={id}` → `openContractRegistration`  
→ modal FirstApproach com `installation_requested` + produto no carrinho  
→ GPS auto → preencher cliente/campos venda → Confirmar venda → toast → mapa

## Campos (venda)

Dependem de `SaleFieldsPolicyResolver` / `saleRequiredFields` da empresa.  
Tipicamente: itens (produto), customer_name/phone/document/etc. conforme config.

| Grupo | Notas |
|-------|-------|
| Produto | Pré-selecionado (seed cart) |
| GPS lat/lng | Obrigatório backend; auto via getGps |
| Customer fields | Conforme política empresa |
| Notes | Opcional |

## Contagens (aprox. happy path)

- Telas: apresentação → mapa/modal (1 transição)  
- Toques UI: 2+ (Contratar + Confirmar) + digitação campos obrigatórios  
- Scroll: possível no modal sale-finalize  

## GPS falhou

Mensagem clara; permite toque no mapa depois (sessionStorage product).  
Não trava silencioso.

## Futuro (não alterar agora)

- Pré-preencher nome/telefone se já conversou no ponto  
- Reduzir campos obrigatórios no campo (config empresa)  
- Confirmação “Cadastro salvo” mais explícita além do toast  

## Preservar

Deep-link, FirstApproach único, ProductPolicy, tenancy.
