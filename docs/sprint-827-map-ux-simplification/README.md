# Sprint 8.2.7 — Simplificação do Mapa (UX do Vendedor)

## Objetivo

Reduzir o cadastro de um ponto no mapa a poucos segundos: **Meu Local → GPS → formulário**, sem etapas intermediárias.

Escopo exclusivo: Blade, CSS, JS de mapa e UX. Sem mudanças de banco, controllers, services, APIs ou regras de negócio.

## Mudanças

1. **Removido** o CTA “Próxima casa” (wrap, botão e estilos).
2. **Cabeçalho** reorganizado: busca + **Meu Local** (primary, ícone `map-pin`); no mobile o texto permanece visível e o botão ganha largura.
3. **“Novo ponto” → “Meu Local”** (toolbar, empty state, painel lateral, título do modal).
4. **Removida** a etapa “Casa sem cadastro”: clique no mapa abre o formulário direto com as coordenadas.
5. **GPS automático** em Meu Local (`navigator.geolocation`); mensagem amigável se negar permissão; permite ajuste manual.
6. Após GPS: **centraliza mapa**, zoom ≥ 17, **marcador rascunho**, formulário aberto com lat/lng (e cidade/setor do filtro quando houver).
7. Tips / brief do vendedor atualizados para o novo fluxo.

## Fluxo novo

```
Meu Local  →  GPS  →  mapa centrado + formulário
Clique no mapa  →  formulário (coords do clique)
```

## Testes

```
.\.tools\php\php.exe artisan test --filter=Sprint827
```

Ver também MapsModuleTest / FirstApproach / PilotSellerUx (asserções alinhadas).

## Artefatos

- [CHANGELOG.md](./CHANGELOG.md)
- [screenshots/](./screenshots/)
