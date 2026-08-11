# AUDIT — Sprint 8.2.30 (antes de alterar)

Inventário do sistema visual no estado validado da 8.2.29 (`34ea25f`).

## Camadas visuais

| Camada | Onde | Papel |
|--------|------|-------|
| ThemeService | `app/Domains/Branding/Services/ThemeService.php` | `--primary`, `--bg`, `--border`, fontes, contraste |
| `client-ui.css` | `public/css/client-ui.css` | Componentes `x-client.*` |
| Layout operacional | `layouts/operational.blade.php` | `.btn`, `.card`, `.table`, `.badge`, `.alert`, rail |
| `rc-ux-polish` | `partials/rc-ux-polish` | Overlay UX residual |
| Tailwind CDN | **somente** `layouts/operational.blade.php` | Mapa + classes utilitárias |
| Lucide CDN | operational + app | `data-lucide` |
| Layout app | `layouts/app.blade.php` | Painel legado (Lucide, sem Tailwind CDN) |
| Layout platform | `layouts/platform.blade.php` | Super Admin, tokens próprios amber |
| Auth | `auth/login`, forgot, reset | ThemeService + CSS inline próprio |
| Mapa | `maps/index.blade.php` | Tailwind slate/sky, sheets 8.2.26, house pins 8.2.29 |

## Respostas da Fase A

### 1. Quantos padrões de botão?

**5 famílias reais, com duplicação de nome:**

| Família | Classes | Uso |
|---------|---------|-----|
| Client | `.btn.btn-primary`, `.btn.btn-ghost`, `.client-btn-danger` | CRM operacional |
| Team | `.team-btn-primary`, `.team-btn-ghost`, `.team-btn-danger` | Equipe (sky hardcoded → mapeado nesta sprint) |
| Map operation | `.map-operation-btn-primary/secondary` | Sheet 8.2.26 (não alterar) |
| Auth | `.btn-primary`, `.btn-trial` | Login (branding) |
| Tailwind ad-hoc | `bg-sky-500 h-14 rounded-xl` | Mapa (CTA campo) |

Blade: `x-client.primary-button`, `secondary-button`, `danger-button`.

### 2. Quantos padrões de card?

**4:** `.card` (layout), `.client-section-card`, `.client-metric-card`, cards Tailwind do mapa (`rounded-2xl bg-slate-950`). Nested cards ocasionais em Produtos (estoque) e Clientes.

### 3. Quantos padrões de input?

**3:** `.form-control` operacional; inputs auth (radius 0.65rem); inputs mapa (`rounded-xl` Tailwind). Altura ~2.5–2.75rem.

### 4. Quantos badges/status?

- `.badge` / `.badge-primary|success|warning|danger` (layout)
- `x-client.status-badge` (tone)
- `.comm-status-*` (comissões)
- `MapMarkerColor` (mapa — **não alterar**)
- Chips de presença Equipe (`.team-filter-chip`)

### 5. Quais layouts usam ThemeService?

- `operational`, `app`, `guest`, auth (login/forgot/reset)
- **Não:** `platform` (tokens locais `#F59E0B`)

### 6. Quais possuem CSS próprio?

Auth, platform, mapa (`@stack` + bloco `<style>` em `maps/index`), Equipe (team-* em client-ui), marketplace/landing.

### 7. Quais usam Tailwind CDN?

Somente `layouts/operational.blade.php` (`cdn.tailwindcss.com`). Lucide: operational + app (`unpkg.com/lucide@0.469.0`).

### 8. Onde há style inline?

Dezenas de blades. Concentração: platform/marketplace, onboarding, customers/show, follow-ups, field-operations, products form. Não eliminar em massa — só onde o header/empty-state substitui chrome.

### 9. Onde existem emojis como ícones?

| Local | Emoji | Decisão 8.2.30 |
|-------|-------|----------------|
| Drawer mapa | 📍 Residência | → copy **Ponto** |
| Pós-visita | ✅ | → Lucide `check-circle-2` |
| Day brief seller | 🏠 ⭐ 📄 | → Lucide |
| Commission reward | 🪙 | **manter** (celebração) |
| Marketplace landing | ✅ ❌ | marketing — defer |
| Onboarding setup | ✅ ⬜ | defer (fluxo setup) |

### 10. Quais páginas destoam visualmente?

| Página | Motivo | Ação 8.2.30 |
|--------|--------|-------------|
| Integrações / Google Maps | header `h1.page-title` custom | page-header |
| Equipe | botões sky `#38bdf8` | mapear para `--primary` |
| Produtos / Minhas visitas / Agenda | empty/header irregulares | page-header + empty-state |
| Erros Laravel default | sem branding | páginas 403/404/419/500 |
| Super Admin | amber próprio, sem client-ui | aliases de token + `client-ui.css` |
| `layouts.app` | rail antigo | **DEFER** (pouco usado vs operational) |
| Marketplace / onboarding | identidade marketing | **não mexer** |

## Duplicações mapeadas

1. `--primary` (ThemeService) vs `--color-primary` (ausente) vs `--accent` (sinônimo).
2. `.btn-primary` vs `.team-btn-primary` vs `bg-sky-500`.
3. `.card` vs `.client-section-card`.
4. Toast `.op-toast` vs `x-client.alert` vs session flash duplicado no layout.
5. Close: Lucide `x` vs texto “Fechar” vs emoji.

## O que NÃO entra nesta sprint

Mapa operacional (JS), house pins, clusters, MapMarkerColor, AppTime, comissões, sessão, password reset, tenancy, Capacitor.
