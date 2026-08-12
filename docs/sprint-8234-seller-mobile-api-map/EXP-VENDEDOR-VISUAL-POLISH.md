# EXP Vendedor — Polimento Visual (Sprint)

## Resumo

Transformação do shell Capacitor de protótipo para interface profissional **EXP Vendedor**, mantendo contratos de API, autenticação Sanctum, `runtime-config.js` e CSP estrita.

## 1. Arquivos alterados

| Arquivo | Alteração |
|---------|-----------|
| `resources/css/exp-vendedor-shell.css` | Design system (paleta EXP, cards, nav, mapa, sheets) |
| `resources/js/mobile/seller-labels.js` | Labels PT-BR centralizados |
| `resources/js/mobile/bootstrap-shell.js` | UI redesenhada, dashboard, cards, mapa, formulários |
| `resources/js/mobile/map-adapter.js` | Camadas Mapa/Satélite (OSM + Esri), GPS circle, clique no mapa |
| `resources/js/mobile/presentation-screen.js` | Tela de apresentação de produtos |
| `resources/js/seller-app.js` | Imports CSS + presentation-screen |
| `scripts/prepare-capacitor-shell.mjs` | HTML shell, assets, CSP (Esri + imagens API) |
| `public/images/exp-vendedor/*` | Estrutura de assets (placeholder até arte oficial) |
| `tests/Feature/Release/ExpVendedorVisualPolishTest.php` | Testes estruturais |

## 2. Componentes criados

- **Design system** — `exp-vendedor-shell.css`
- **Labels PT-BR** — `seller-labels.js` (`commissionStatusLabel`, `propertyStatusLabel`, `formatCurrency`)
- **PresentationScreen** — listagem de produtos via `GET /api/mobile/v1/products`
- **MapAdapter basemaps** — rua (OSM) e satélite (Esri World Imagery, gratuito)

## 3. Telas redesenhadas

- Login — logo EXP, EXP VENDEDOR, subtítulo porta a porta
- Cabeçalho compacto — logo + Olá + empresa + botão perfil
- Mapa — Meu Local, camadas, novo imóvel; lat/lng ocultos
- Agenda — empty state + cards de retorno
- Clientes — busca + cards com Ver no mapa / Ver detalhes
- Resultado — dashboard com cards (visitas, retornos, vendas, comissão)
- Comissão — cards com badges Pago/Pendente/Aprovada
- Mais — menu (produtos, conta, sobre, sair) + versão
- Apresentar produtos — overlay fullscreen

## 4. Traduções implementadas

Centralizadas em `seller-labels.js`:

- Comissão: `pending` → Pendente, `approved` → Aprovada, `paid` → Pago
- Imóvel: status comerciais (Novo, Venda realizada, etc.)
- UI: Sem conexão, mensagens de toast, labels de formulário em PT-BR

## 5. Funcionalidades conectadas à API

| Tela | Endpoint |
|------|----------|
| Bootstrap / sessão | `GET /api/mobile/v1/bootstrap` |
| Mapa marcadores | `GET /api/mobile/v1/markers` |
| Novo imóvel | `POST /api/mobile/v1/points` (lat/lng hidden) |
| Detalhe / visita | `GET/POST /api/mobile/v1/points/{id}` |
| Agenda | `GET /api/mobile/v1/agenda` |
| Clientes | `GET /api/mobile/v1/points` |
| Resultado | `GET /api/mobile/v1/results` |
| Comissão | `GET /api/mobile/v1/commissions` |
| Produtos | `GET /api/mobile/v1/products` |
| Território | `GET /api/mobile/v1/territory` |

## 6. Dependências de backend / gaps

| Recurso | Status |
|---------|--------|
| Conversão (KPI) | **Não exposta** em `/results` — card exibe "—" |
| Data de pagamento comissão | **Não retornada** — só `earned_at` |
| Benefícios detalhados produto | **Não no contrato mobile** — só `name`, `description`, `price`, `image` |
| Apresentação rica (slides web) | Fluxo Blade web; mobile usa listagem de catálogo |
| Reverse geocode endereço | **Não disponível** no projeto mobile — coordenadas no chip |
| Configurações | Item reservado — sem endpoint mobile dedicado |

## 7. Assets oficiais

Substituir placeholders em `public/images/exp-vendedor/` conforme `README.md`:

- `logo-exp.svg` — ícone/login/cabeçalho
- `splash-mark.svg` — splash Android (via Capacitor config futura)
- `icon-android.png` — quando fornecido

**Não redesenhar a marca** — apenas trocar arquivos.

## 8. Build e sync

```bash
set CAP_API_URL=https://expandor.unicanetwork.com.br
npm run build
npx cap sync android
```

Validar: `android/app/src/main/assets/public/runtime-config.js` presente.

## 9. Riscos / regressões

- CSP `img-src` inclui origem da API + Esri — testar imagens de produto em produção
- Placeholders SVG não são arte final — trocar antes de release store
- Camada satélite Esri requer atribuição (mantida via Leaflet)
- Clique no mapa abre cadastro — pode conflitar com zoom/pan em gestos rápidos
