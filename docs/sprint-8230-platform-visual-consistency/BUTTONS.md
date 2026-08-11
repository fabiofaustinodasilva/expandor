# BUTTONS

## Variantes

| Variante | Classe / componente | Uso |
|----------|---------------------|-----|
| PRIMARY | `.btn.btn-primary` / `x-client.primary-button` | Ação principal |
| SECONDARY | `.btn.btn-ghost` / `x-client.secondary-button` | Alternativa / voltar |
| GHOST | `.btn-ghost` / `.team-btn-ghost` | Navegação leve |
| DANGER | `.btn-danger` / `x-client.danger-button` | Excluir |
| ICON | `.btn-icon` / `.team-icon-btn` | Toolbar / fechar |

## Contrato visual

- Altura: `--control-height` (2.75rem) / touch 44px
- Radius: 0.75rem
- Font-weight: 700
- Focus: `:focus-visible` outline 2px `--primary`
- Disabled: opacity 0.45, `cursor: not-allowed`
- Loading: spinner do botão existente onde já houver; sem animação nova

## Equipe

`.team-btn-primary` deixa de usar sky `#38bdf8` e usa `var(--primary)`.

## Mapa

CTAs do Map Operation Sheet (`map-operation-btn-*`) **não** foram reescritos — referência positiva da 8.2.26.

## CTA copy

Verbos curtos: Salvar, Aprovar, Marcar pago, Novo produto, Cadastrar produto, Abrir mapa, Voltar, Conectar. Verbo + objeto só quando o contexto não é óbvio.
