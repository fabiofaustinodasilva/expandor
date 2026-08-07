# AUDIT — Sprint 8.2.8

## Superfície principal

O trabalho de campo do vendedor concentra-se em **`/map`** (`maps.index`), não em `/app` (Sales App em listas). A 8.2.8 reforça o mapa como hub.

## Estado herdado (8.2.7)

| Item | Status pré-8.2.8 |
|------|------------------|
| Meu Local → GPS → form | Já existia |
| Clique no mapa → form direto | Já existia |
| Remoção “Casa sem cadastro” / “Próxima casa” | Já feita |
| Botão só recentralizar GPS | **Ausente** |
| Lat/lng crus no formulário do vendedor | Visíveis / técnicos |
| Pós-salvar field seller | Abría drawer + `openPostCreateAdjust` |
| Copy GPS negado | Genérica (“Não foi possível obter…”) |

## Domínio de situação (reutilizado, não inventado)

`VisitStatus` / botões `point-outcome`:

- `interested` → Interessado  
- `no_interest` → Não interessado  
- `return_later` → Retornar depois  
- `installation_requested` → label comercial (`CommercialTerminology::saleCompleted()`, ex. Venda/Instalação)  
- `not_home` → Não encontrado  

Cores de marcador comercial: grupos existentes em `map-provider.js` — **não** se criou estado só para visual (retorno vs não interesse podem compartilhar grupo visual).

## Arquivos envolvidos

| Área | Arquivos |
|------|----------|
| View | `resources/views/maps/index.blade.php` |
| JS | `public/js/operational-map.js` |
| Cores/map provider | `public/js/map-provider.js` (sem mudança de domínio) |
| Controllers (somente leitura) | `MapController`, first-approach / point map controllers |
| Enums | `VisitStatus`, `PropertyStatus` |
| Testes | `Sprint828…`, ajuste mensagem GPS em `Sprint827…` |

## Rotas

- `map.index` — UI  
- Endpoints de create/first-approach existentes — **inalterados**

## Problemas encontrados (UX)

1. Sem atalho “voltar à minha posição” sem criar ponto.  
2. Feedback pós-salvar burocrático para vendedor (ajuste de pin).  
3. Copy GPS / erro de save pouco alinhadas ao briefing de campo.  
4. Formulário ainda com metadados técnicos para seller.  
5. Sales App (`/app`) continua paralelo — fora do núcleo desta sprint.

## Riscos restantes

- Distinção visual mapa entre `return_later` e `no_interest` exigiria mudança de agrupamento/cores (**Backend / domínio — não feito**).  
- Teclado mobile em viewports reais: validar checklist; testes automatizados cobrem HTML/JS, não WebDriver visual.  
- `/app` sem parity completa com o mapa.

## Segurança / permissões

Policies/Gates existentes preservadas. Seller não recebe links admin/billing/users/permissions no mapa. Sem alteração de roles.
