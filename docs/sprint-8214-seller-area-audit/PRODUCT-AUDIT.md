# PRODUCT-AUDIT

## Fluxo validado (8.2.12–8.2.13)

Mapa → Apresentar → foto full-screen → swipe → Detalhes → Contratar → mapa

## Tempos / toques

| Passo | Toques | Meta <5s? |
|-------|--------|-----------|
| Abrir deck do mapa | 1 | Sim (se catálogo já no servidor) |
| Próximo produto | swipe / 1 | Sim (local) |
| Detalhes | 1 | Sim (JSON local) |
| Contratar | 1 (+ navegação mapa) | Depende GPS/rede |

**Resposta:** Sim — vendedor pode começar a mostrar produto em **&lt; 5 s** após 1 toque, desde que: (1) `sales_app.access` + flag mobile; (2) há produtos ativos; (3) rede ok no GET inicial do deck.

## Mídia

- Com imagem: `object-fit: contain` full-bleed  
- Sem imagem: fallback com nome (não vazio total)  
- Vídeo: URL / embed; sem upload binário  

## Detalhes (bottom-sheet)

Mostra: nome, categoria, descrição, benefícios, preço.  
Útil como **mini-treinamento** no campo (empresa atualiza catálogo → seller consulta).

## Lacuna catálogo vs Academia

| Catálogo | Academia |
|----------|----------|
| Produto comercial + materiais | Conteúdos/categorias/progresso |
| No momento da venda | Estudo prévio |

Não duplicar: manter Detalhes como apoio comercial; Academia como treino estruturado (P2 no rail).

## Riscos

- Produto desativado enquanto deck aberto: só some no próximo reload  
- Catálogo vazio: empty state no deck  
- Rail “Apresentar” → `present`; Sales App bottom → `products.index` (inconsistência)
