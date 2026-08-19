# Screenshots no site

Originais (não alterar nesta pasta): `docs/expandor-commercial-presentation/screenshots/`.

Derivados para a web: `public/images/marketplace/product/*.webp` + `*.png`.
Regenerar: `npm run marketplace:product-shots`.

| Uso no site | Arquivo original | Shot derivado |
|---|---|---|
| Hero | 01-hero-mapa-operacional.png | `hero-mapa` |
| Jornada / mapa | 03-inteligencia-ponto.png | `inteligencia-ponto` |
| Campo — Mapa | 04-exp-vendedor-mapa.png | `exp-mapa` |
| Campo — Agenda | 05-exp-vendedor-agenda.png | `exp-agenda` |
| Campo — Produtos | 06-exp-vendedor-produtos.png | `exp-produtos` |
| Jornada — Contratação | 08-exp-vendedor-venda.png | `exp-venda` |
| Campo — Venda + seção venda | 09-exp-vendedor-venda-realizada.png | `exp-venda-realizada` |
| Campo — Resultados | 10-exp-vendedor-resultado.png | `exp-resultado` |
| Campo — Comissão | 11-exp-vendedor-comissao.png | `exp-comissao` |
| Gestor | 02, 14, 15 | `dashboard-gestor`, `equipe-gestor`, `financeiro-gestor` |

Não usados nesta sprint (existem no pack, sem narrativa na home): 07, 12, 13, 16.

Hero: `fetchpriority="high"`. Demais: `loading="lazy"`, `width`/`height`, `<picture>` WebP com PNG fallback.

## QR Code (futuro)

Ponto de inserção: `.mkp-qr-slot` no CTA final da landing.
Ativar só quando houver `data-qr-src` com URL/WhatsApp comercial definitivo. **Não gerar QR nesta sprint.**
